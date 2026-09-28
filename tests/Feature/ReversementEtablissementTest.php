<?php

namespace Tests\Feature;

use App\Jobs\ReverserEtablissementJob;
use App\Mail\AlerteReversementManquantMail;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use App\Services\AangaraaPayService;
use App\Support\MontantPaiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * P1 — l'argent bloqué chez AangaraaPay.
 *
 * Ces tests couvrent ce qui n'existait pas avant : un reversement échoué
 * devenait invisible (statut 'calculee' pour toujours, aucune alerte, aucun
 * moyen de rejouer) et un résultat HTTP perdu pouvait déclencher un second
 * virement à l'établissement.
 */
class ReversementEtablissementTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private Paiement $paiement;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Queue::fake();

        $this->etab = Etablissement::create([
            'code_etablissement'  => 'ETAB-REV',
            'nom'                  => 'Ecole Reversement',
            'type'                 => 'lycee_general',
            'statut_juridique'     => 'prive_laic',
            'region'               => 'centre',
            'ville'                => 'Yaounde',
            'telephone'            => '650000000',
            'email'                => 'admin@rev.test',
            'taux_commission'      => 0.05,
            'numero_momo_reversement' => '650123456',
            'operateur_momo_reversement' => 'mtn',
            'statut'               => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Eleve',
            'prenom'           => 'Reversement',
            'classe'           => '1ere',
            'statut_paiement'  => 'impaye',
            'actif'            => true,
        ]);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'fractionnable'    => false,
            'nb_tranches_max'  => 1,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id'        => $apprenant->id,
            'categorie_frais_id'  => $categorie->id,
            'montant_total'       => 50000,
            'montant_paye'        => 0,
            'statut'              => 'impaye',
        ]);

        $this->paiement = Paiement::create([
            'user_id'           => User::factory()->create()->id,
            'apprenant_id'      => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'           => 50000,
            'frais_service'     => 800,
            'montant_total_paye' => 50800,
            'mode_paiement'     => 'mtn_momo',
            'statut'            => 'valide',
        ]);
    }

    /**
     * Paiement distinct : la contrainte d'unicite commissions.paiement_id
     * interdit deux commissions pour un meme paiement (protection contre le
     * double comptage), donc chaque commission de test a besoin du sien.
     */
    private function creerPaiement(int $montant = 50000): Paiement
    {
        $frais = FraisApprenant::first();
        $frais->update(['montant_total' => $montant, 'montant_paye' => 0]);

        return Paiement::create([
            'user_id'           => User::factory()->create()->id,
            'apprenant_id'      => $frais->apprenant_id,
            'frais_apprenant_id' => $frais->id,
            'montant'           => $montant,
            'frais_service'     => 800,
            'montant_total_paye' => $montant + 800,
            'mode_paiement'     => 'mtn_momo',
            'statut'            => 'valide',
        ]);
    }

    private function commission(
        string $statut = Commission::STATUT_CALCULEE,
        int $net = 50000,
        ?Paiement $paiement = null
    ): Commission {
        return Commission::create([
            'paiement_id'                => ($paiement ?? $this->paiement)->id,
            'etablissement_id'           => $this->etab->id,
            'montant_transaction'        => 50000,
            'taux'                       => 0.05,
            'montant_commission'         => 2500,
            'statut'                     => $statut,
            'montant_net_etablissement'  => $net,
            'frais_aangaraa'             => 1100,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Cas nominal
    // ─────────────────────────────────────────────────────────────

    public function test_reversement_reussi_passe_en_prelevee_avec_reference(): void
    {
        Http::fake([
            '*/withdrawal' => Http::response([
                'status'  => 'SUCCESS',
                'message' => 'Transfert enregistre',
                'data'    => ['transaction_id' => 'REV-OK-1', 'operator' => 'MTN_Cameroon'],
            ], 200),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->statut);
        $this->assertSame('REV-OK-1', $commission->reference_reversement);
        $this->assertNotNull($commission->reversed_at);
        $this->assertNull($commission->reversement_erreur);
    }

    public function test_commission_deja_prelevee_ne_declenche_aucun_appel(): void
    {
        Http::fake();

        $commission = $this->commission(Commission::STATUT_PRELEVEE);

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        Http::assertNothingSent();
        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->fresh()->statut);
    }

    // ─────────────────────────────────────────────────────────────
    // P1 — plus d'échec muet
    // ─────────────────────────────────────────────────────────────

    public function test_numero_de_reversement_manquant_met_en_echec_avec_alerte(): void
    {
        Http::fake();

        $this->etab->update(['numero_momo_reversement' => null]);
        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_ECHEC, $commission->statut);
        $this->assertStringContainsString('numero_momo_reversement', $commission->reversement_erreur);
        Http::assertNothingSent();

        // L'argent ne peut plus rester bloqué sans que personne ne le sache.
        Mail::assertSent(AlerteReversementManquantMail::class);
    }

    public function test_numero_de_reversement_invalide_met_en_echec(): void
    {
        Http::fake();

        $this->etab->update(['numero_momo_reversement' => '12345']); // pas un 6XXXXXXXX
        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_ECHEC, $commission->statut);
        $this->assertStringContainsString('invalide', $commission->reversement_erreur);
        Http::assertNothingSent();
    }

    public function test_une_commission_deja_reservee_ne_declenche_pas_un_second_virement(): void
    {
        Http::fake([
            '*/withdrawal' => Http::response([
                'status'  => 'SUCCESS',
                'message' => 'Transfert enregistre',
                'data'    => ['transaction_id' => 'REV-OK-1', 'operator' => 'MTN_Cameroon'],
            ], 200),
        ]);

        $commission = $this->commission();

        // Premier passage : la reservation atomique pose 'en_cours'.
        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();
        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->statut);
        Http::assertSentCount(1);

        // Second passage sur la meme commission (rejeu de la commande
        // aangaraa:reversements, double dispatch) : l'UPDATE conditionnel
        // ne reserve rien, le virement ne repart pas.
        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();
        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->statut);
        Http::assertSentCount(1);
    }

    public function test_une_commission_prelevee_ne_repart_rien(): void
    {
        Http::fake();

        // Une commission deja prelevee ne peut pas etre reservee de nouveau :
        // ni l'UPDATE conditionnel, ni l'appel HTTP ne repartent.
        $commission = $this->commission(Commission::STATUT_PRELEVEE);

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        Http::assertNothingSent();
        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->fresh()->statut);
    }

    public function test_refus_aangaraa_laisse_la_commission_rejouable(): void
    {
        Http::fake([
            '*/withdrawal' => Http::response(['message' => 'Solde insuffisant'], 400),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        // Un refus net n'a rien envoye : la commission redevient eligible au
        // rejeu automatique. Elle n'est plus piegee dans un etat terminal
        // inconnu, et la trace de la tentative est conservee.
        $this->assertSame(Commission::STATUT_CALCULEE, $commission->statut);
        $this->assertNotNull($commission->reversement_tente_le);

        Http::assertSentCount(1);
    }

    public function test_echec_technique_declenche_l_alerte_meme_si_failed_est_appele(): void
    {
        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))
            ->failed(new \RuntimeException('AangaraaPay injoignable'));

        $commission->refresh();

        $this->assertSame(Commission::STATUT_ECHEC, $commission->statut);
        $this->assertStringContainsString('AangaraaPay injoignable', $commission->reversement_erreur);

        Mail::assertSent(AlerteReversementManquantMail::class);
    }

    // ─────────────────────────────────────────────────────────────
    // P1 — protection contre le double virement
    // ─────────────────────────────────────────────────────────────

    public function test_reponse_indetermine_met_en_a_verifier_sans_relancer(): void
    {
        // Timeout / 5xx : l'argent est peut-être parti, on ne rejoue JAMAIS
        // tout seul, sinon l'établissement est payé deux fois.
        Http::fake([
            '*/withdrawal' => Http::response(['message' => 'Service indisponible'], 500),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);
        $this->assertTrue($commission->requiertIntervention());
        Http::assertSentCount(1);
        Mail::assertSent(AlerteReversementManquantMail::class);
    }

    public function test_2xx_sans_reference_est_traite_comme_indetermine(): void
    {
        Http::fake([
            '*/withdrawal' => Http::response(['status' => 'SUCCESS', 'data' => []], 200),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->fresh()->statut);
    }

    public function test_reversement_orphelin_en_cours_passe_en_a_verifier_sans_rappel(): void
    {
        Http::fake();

        // Un essai precedent a ete engage (statut 'en_cours' pose avant
        // l'appel) mais son sort n'a jamais ete enregistre : crash, kill -9,
        // redemarrage de PHP. Relancer automatiquement payerait deux fois.
        $commission = $this->commission(Commission::STATUT_EN_COURS);

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);
        Http::assertNothingSent();
    }

    public function test_commission_en_a_verifier_nest_jamais_rejouee_automatiquement(): void
    {
        Http::fake();

        $commission = $this->commission(Commission::STATUT_A_VERIFIER);

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->fresh()->statut);
        Http::assertNothingSent();
    }

    // ─────────────────────────────────────────────────────────────
    // P1 — commande de rejeu
    // ─────────────────────────────────────────────────────────────

    public function test_commande_rejeu_ne_traite_que_les_commissions_calculees(): void
    {
        Queue::fake();

        $aPayer    = $this->commission();
        $incertaine = $this->commission(Commission::STATUT_A_VERIFIER, 50000, $this->creerPaiement());
        $enEchec   = $this->commission(Commission::STATUT_ECHEC, 50000, $this->creerPaiement());

        $this->artisan('aangaraa:reversements:rejouer')
            ->assertSuccessful();

        Queue::assertPushed(ReverserEtablissementJob::class, 1);
        Queue::assertPushed(ReverserEtablissementJob::class, fn ($job) => $job->commissionId === $aPayer->id);

        $this->assertSame(Commission::STATUT_A_VERIFIER, $incertaine->fresh()->statut);
        $this->assertSame(Commission::STATUT_ECHEC, $enEchec->fresh()->statut);
    }

    public function test_commande_rejeu_ne_peut_inclure_a_verifier_qu_explicitement(): void
    {
        Queue::fake();

        $incertaine = $this->commission(Commission::STATUT_A_VERIFIER, 50000, $this->creerPaiement());

        $this->artisan('aangaraa:reversements:rejouer', ['--inclure-a-verifier' => true])
            ->assertSuccessful();

        Queue::assertPushed(ReverserEtablissementJob::class, 1);
        Queue::assertPushed(ReverserEtablissementJob::class, fn ($job) => $job->commissionId === $incertaine->id);
    }

    public function test_commande_rejeu_en_dry_run_nenvoie_rien(): void
    {
        Queue::fake();

        $this->commission();

        $this->artisan('aangaraa:reversements:rejouer', ['--dry-run' => true])
            ->expectsOutputToContain('aucun envoi effectue')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_commande_rejeu_signale_une_notify_url_incoherente(): void
    {
        config(['services.aangaraa.notify_url' => 'https://mauvais-domaine.test/webhook/aangaraapay']);
        config(['app.url' => 'https://edupay.mekontso.gsi2026.com']);

        $this->commission();

        $this->artisan('aangaraa:reversements:rejouer', ['--dry-run' => true])
            ->expectsOutputToContain('AANGARAA_NOTIFY_URL')
            ->assertSuccessful();
    }

    // ─────────────────────────────────────────────────────────────
    // P1 — cout reel du reversement (2,2 % et non 2 %)
    // ─────────────────────────────────────────────────────────────

    public function test_frais_suivent_le_taux_global_a_chaque_montant(): void
    {
        $service = new AangaraaPayService();

        // 50 000 FCFA de frais de scolarite : 2,3 % de frais (2,2 % de cout
        // AangaraaPay + 0,1 % de marge EduPay).
        $detail = $service->calculerFrais(50000);

        $this->assertSame(1150, $detail['frais_service']);
        $this->assertSame(1100, $detail['frais_aangaraa']);
        $this->assertSame(50, $detail['marge_edupay']);
        $this->assertSame(51150, $detail['montant_total_paye']);
    }

    public function test_taux_interne_correspond_a_la_commission_mesuree(): void
    {
        $this->assertSame(0.022, AangaraaPayService::TAUX_AANGARAA_DEFAUT);

        $detail = (new AangaraaPayService())->calculerFrais(100000);

        // 2 300 de frais visibles, dont 2 200 preleves par le prestataire.
        $this->assertSame(2300, $detail['frais_service']);
        $this->assertSame(2200, $detail['frais_aangaraa']);
        $this->assertSame(100, $detail['marge_edupay']);
    }

    public function test_marge_jamais_negative_sur_toute_la_gamme(): void
    {
        $service = new AangaraaPayService();

        // Ancien defaut : a 50 000 FCFA les frais visibles (800) etaient
        // inferieurs au cout du reversement (1 100), et max(0, ...) masquait
        // la perte. L'arrondi ne doit pas reintroduire ce cas.
        foreach ([1000, 5000, 10000, 25000, 50000, 100000, 200000, 1000000] as $montant) {
            $detail = $service->calculerFrais($montant);

            $this->assertGreaterThanOrEqual(
                0,
                $detail['marge_edupay'],
                "Marge negative sur '.$montant.' FCFA"
            );
            $this->assertSame(
                $detail['montant_frais'] + $detail['frais_service'],
                $detail['montant_total_paye'],
                'Le total debite doit etre la somme des deux lignes'
            );
        }
    }

    public function test_etablissement_recoit_exactement_les_frais_de_scolarite(): void
    {
        $service = new AangaraaPayService();
        $detail  = $service->calculerFrais(50000);

        // Ce que le payeur debite entre sur le compte AangaraaPay...
        $this->assertSame(51150, $detail['montant_total_paye']);

        // ...ce que l'etablissement recoit vaut les seuls frais de scolarite,
        // donc la difference reste sur le compte : 50 FCFA de marge.
        $net = $detail['montant_frais'];

        $this->assertSame(50000, $net);
        $this->assertSame(
            $detail['montant_total_paye'] - $net,
            $detail['frais_service'],
            'Le solde conserve doit etre exactement les frais de service'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // P3 — notify_url : un domaine mort doit etre detectable
    // ─────────────────────────────────────────────────────────────

    public function test_notify_url_locale_est_refusee(): void
    {
        $controle = (new AangaraaPayService())->verifierNotifyUrl('http://localhost:8000/webhook/aangaraapay');

        $this->assertFalse($controle['ok']);
    }

    public function test_notify_url_different_du_domaine_de_production_est_refusee(): void
    {
        config(['app.url' => 'https://edupay.mekontso.gsi2026.com']);

        // Coquille de domaine : gsi2026 écrit qsi2026. Silencieuse avant.
        $controle = (new AangaraaPayService())
            ->verifierNotifyUrl('https://edupay.mekontso.qsi2026.com/webhook/aangaraapay');

        $this->assertFalse($controle['ok']);
        $this->assertStringContainsString('callbacks', $controle['raison']);
    }

    public function test_notify_url_coherente_est_acceptee(): void
    {
        config(['app.url' => 'https://edupay.mekontso.gsi2026.com']);

        $controle = (new AangaraaPayService())
            ->verifierNotifyUrl('https://edupay.mekontso.gsi2026.com/webhook/aangaraapay');

        $this->assertTrue($controle['ok']);
    }

    public function test_notify_url_vide_est_refusee(): void
    {
        $service = new AangaraaPayService();

        $this->assertFalse($service->verifierNotifyUrl(null)['ok']);
        $this->assertFalse($service->verifierNotifyUrl('')['ok']);
    }

    // ─────────────────────────────────────────────────────────────
    // P2 — minimum operateur reellement applique
    // ─────────────────────────────────────────────────────────────

    public function test_montant_inferieur_au_minimum_est_refuse_par_le_serveur(): void
    {
        $frais = FraisApprenant::first();
        $frais->update(['montant_total' => 10, 'montant_paye' => 0]);

        $calcul = app(MontantPaiement::class)->calculer($frais->fresh(), 'integral');

        // Avant : 10 FCFA partaient vers AangaraaPay (le seul garde-fou etait
        // un min:50 sur un champ client que le serveur ignore).
        $this->assertSame(MontantPaiement::ERREUR_MONTANT_INVALIDE, $calcul['erreur']);
    }

    public function test_montant_au_minimum_exact_est_accepte(): void
    {
        $frais = FraisApprenant::first();
        $frais->update(['montant_total' => MontantPaiement::MONTANT_MINIMUM, 'montant_paye' => 0]);

        $calcul = app(MontantPaiement::class)->calculer($frais->fresh(), 'integral');

        $this->assertNull($calcul['erreur']);
        $this->assertSame(50, $calcul['montant']);
    }

    public function test_option_tranche_invalide_nest_pas_proposee(): void
    {
        $frais = FraisApprenant::first();
        $frais->update(['montant_total' => 10, 'montant_paye' => 0]);

        $calcul = app(MontantPaiement::class)->calculer($frais->fresh(), 'tranche');

        $this->assertNotNull($calcul['erreur']);
    }
}
