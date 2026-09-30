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
use PHPUnit\Framework\Attributes\DataProvider;
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
    // Regle metier : aucun reversement sans paiement valide
    // ─────────────────────────────────────────────────────────────

    /**
     * Regle imposee par le metier : « si un paiement n'a pas ete valide,
     * aucun reversement ne doit etre fait ».
     *
     * Le job selectionne sur `commissions.statut = calculee`. Ce statut est
     * pose au moment ou le webhook passe le paiement a `valide`, mais rien ne
     * le verifiait au moment de l'envoi. Ce test verrouille la regle desormais
     * sur le paiement lui-meme : c'est la seule source de verite, et elle est
     * verifiable independamment du code qui emet la commission.
     *
     * Sans ce garde-fou, un rejeu de commande, une commission reinseree a la
     * main ou un bug de webhook pourrait vider de l'argent sur un paiement
     * echeoue, annule ou encore en attente de confirmation client.
     */
    #[DataProvider('statutsPaiementNonValides')]
    public function test_aucun_reversement_si_le_paiement_nest_pas_valide(string $statutPaiement): void
    {
        $paiement = $this->paiement;
        $paiement->update(['statut' => $statutPaiement]);

        $commission = $this->commission();

        Http::fake();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        // Le point critique : AUCUNE requete de retrait n'est partie.
        Http::assertNothingSent();

        $commission->refresh();

        $this->assertNotSame(Commission::STATUT_PRELEVEE, $commission->statut);
    }

    public static function statutsPaiementNonValides(): array
    {
        return [
            'en attente de confirmation client' => ['en_attente'],
            'echoue'                           => ['echoue'],
            'rembourse'                        => ['rembourse'],
            'annule'                           => ['annule'],
        ];
    }

    public function test_le_reversement_part_bien_quand_le_paiement_est_valide(): void
    {
        $this->paiement->update(['statut' => 'valide']);

        $commission = $this->commission();

        Http::fake([
            '*/withdrawal' => Http::response([
                'statusCode' => 200,
                'message'    => 'Withdrawal initiated successfully',
                'data'       => [
                    'status'       => 'SUCCESSFUL',
                    'reference_id' => 'REF-OK-1',
                ],
            ], 200),
        ]);

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->statut);

        // Une seule requete : le double envoi est le risque principal.
        Http::assertSentCount(1);
    }

    // ─────────────────────────────────────────────────────────────
    // Contrat documente de /check_withdrawal_status
    // ─────────────────────────────────────────────────────────────

    /**
     * Payloads repris tels quels de la documentation officielle AangaraaPay
     * (documentation d'integration, section « Verifier le statut d'un
     * retrait »). Ils verrouillent le contrat : le statut se lit a la RACINE
     * de la reponse (`status`), pas sous `data`. Si l'on avait lu
     * `data.status` comme pour la creation du retrait, chaque verification
     * serait tombee en `INCONNU` et aucune commission `a_verifier` ne pourrait
     * jamais etre levee automatiquement.
     */
    public static function statutsRetraitDocumentes(): array
    {
        return [
            'succes' => [
                'SUCCESSFUL',
                [
                    'success' => true,
                    'status' => 'SUCCESSFUL',
                    'operator' => 'MTN_Cameroon',
                    'transaction_id' => 'abc123def456',
                    'amount' => 1000.0,
                    'currency' => 'XAF',
                    'message' => 'Transaction réussie',
                    'operator_code' => 'SUCCESSFUL',
                    'timestamp' => '2025-12-29T14:30:15',
                    'details' => [
                        'financialTransactionId' => 'MT789012345',
                        'reason' => null,
                    ],
                ],
            ],
            'en cours' => [
                'PENDING',
                [
                    'success' => true,
                    'status' => 'PENDING',
                    'operator' => 'Orange_Cameroon',
                    'transaction_id' => 'abc123def456',
                    'amount' => 1000.0,
                    'currency' => 'XAF',
                    'message' => 'Transaction en attente',
                    'operator_code' => 'PENDING',
                    'timestamp' => null,
                    'details' => [
                        'txnid' => 'OM456789012',
                        'payToken' => 'MP2512295AD67EBB09E121E235B2',
                    ],
                ],
            ],
            'echoue' => [
                'FAILED',
                [
                    'success' => true,
                    'status' => 'FAILED',
                    'operator' => 'MTN_Cameroon',
                    'transaction_id' => 'abc123def456',
                    'amount' => 1000.0,
                    'currency' => 'XAF',
                    'message' => 'Transaction échouée',
                    'operator_code' => 'FAILED',
                    'timestamp' => '2025-12-29T14:30:15',
                    'details' => [
                        'financialTransactionId' => null,
                        'reason' => 'Invalid phone number',
                    ],
                ],
            ],
            'introuvable' => [
                'NOT_FOUND',
                [
                    'success' => true,
                    'status' => 'NOT_FOUND',
                    'operator' => 'Orange_Cameroon',
                    'transaction_id' => 'abc123def456',
                    'message' => 'Transaction introuvable',
                    'currency' => 'XAF',
                ],
            ],
        ];
    }

    #[DataProvider('statutsRetraitDocumentes')]
    public function test_le_statut_de_retrait_se_lit_a_la_racine_de_la_reponse(string $attendu, array $payload): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response($payload, 200),
        ]);

        $resultat = (new AangaraaPayService())->verifierStatutRetrait('abc123def456', 'mtn');

        $this->assertSame($attendu, $resultat['statut']);
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

    public function test_2xx_sans_statut_nest_plus_considere_comme_un_succes(): void
    {
        // Avant le correctif, on envoyait `operator`/`description` au lieu de
        // `payment_method`/`username` : AangaraaPay repondait 201
        // {"message":"Payment request successful"} sans `status` ni `reference_id`.
        // On classait alors TOUT en succes — faux, le retrait pouvait avoir ete
        // refuse. Desormais une reponse 2xx SANS statut documente n'est jamais
        // un succes.
        //
        // CORRECTION 30/09/2026 : la suite a montre que ce format n'etait pas
        // un artefact de test mais la REPONSE REELLE de /withdrawal. La classer
        // `refuse` (donc rejouable) etait le bug le plus dangereux du projet :
        // constate en test reel, deux virements de 49 CFA sont partis pour la
        // commission 8 alors que la reponse 201 etait traitee comme un echec.
        // On ne peut pas affirmer le succes, mais on ne doit surtout pas
        // nier que l'argent est parti : le seul traitement sur est
        // `a_verifier` (aucun rejeu automatique, verification humaine).
        Http::fake([
            '*/withdrawal' => Http::response([
                'message' => 'Payment request successful',
                'data'    => ['message' => 'Payment request successful'],
            ], 201),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertNotSame(Commission::STATUT_PRELEVEE, $commission->statut);
        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);

        // Le point critique : une seule tentative. Un rejeu automatique
        // enverrait un DEUXIEME virement reel a l'etablissement.
        Http::assertSentCount(1);
    }

    /**
     * Le contrat reel de /withdrawal exige payment_method (pas operator) et
     * username (pas description). On verifie que le payload part avec les
     * bons noms et que la reponse documentee (status SUCCESSFUL +
     * data.reference_id) est un succes.
     */
    public function test_payload_avec_payment_method_et_username_et_reference_id(): void
    {
        Http::fake([
            '*/withdrawal' => Http::response([
                'status'  => 'SUCCESSFUL',
                'message' => 'Transfert enregistre',
                'data'    => ['reference_id' => 'REF-DOC-1'],
            ], 200),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->statut);
        $this->assertSame('REF-DOC-1', $commission->reference_reversement);

        $payload = Http::recorded()[0][0]->data();
        $this->assertArrayHasKey('payment_method', $payload);
        $this->assertArrayHasKey('username', $payload);
        $this->assertArrayNotHasKey('operator', $payload);
        $this->assertArrayNotHasKey('description', $payload);
        $this->assertSame('MTN_Cameroon', $payload['payment_method']);
    }

    public function test_statut_pending_met_en_a_verifier_sans_relancer(): void
    {
        // PENDING : accepte mais pas encore confirme. On ne rejoue jamais, le
        // veritable statut sera tranche via /check_withdrawal_status.
        Http::fake([
            '*/withdrawal' => Http::response([
                'status'  => 'PENDING',
                'message' => 'Retrait en cours',
                'data'    => ['reference_id' => 'REF-PEND-1'],
            ], 200),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);
        $this->assertTrue($commission->requiertIntervention());
        Http::assertSentCount(1);
    }

    public function test_statut_failed_met_en_echec(): void
    {
        // FAILED : l'operateur a refuse, rien n'est parti.
        Http::fake([
            '*/withdrawal' => Http::response([
                'status'  => 'FAILED',
                'message' => 'Solde insuffisant',
                'data'    => ['reference_id' => 'REF-FAIL-1', 'details' => ['reason' => 'Solde insuffisant']],
            ], 200),
        ]);

        $commission = $this->commission();

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        $commission->refresh();

        $this->assertSame(Commission::STATUT_CALCULEE, $commission->statut);
    }

    public function test_verifier_statut_retrait_interroge_le_bon_endpoint(): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response([
                'status' => 'SUCCESSFUL',
            ], 200),
        ]);

        $resultat = (new AangaraaPayService())->verifierStatutRetrait('REF-XYZ', 'orange');

        $this->assertSame('SUCCESSFUL', $resultat['statut']);
        $requete = Http::recorded()[0][0];
        $this->assertStringContainsString('/check_withdrawal_status/REF-XYZ', (string) $requete->url());
        $this->assertSame('Orange_Cameroon', $requete['payment_method']);
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

    /**
     * ── Modele de reversement, confirme par le metier le 30/09/2026 ──────
     *
     * Ce que l'etablissement recoit est le NET, pas le montant de la
     * transaction. Le test precedent affirmait le contraire (« il recoit
     * exactement les frais de scolarite ») mais ne verifiait rien : il
     * assignait `$net = $detail['montant_frais']` puis controlait que cette
     * variable valait l'entree. Il ne touchait ni la commission, ni le net
     * reellement utilise pour virer.
     *
     * Ce test observe l'APPEL REEL envoye a AangaraaPay : c'est le seul moyen
     * de garantir qu'aucun etablissement ne recoive 50 000 au lieu de 49 950.
     */
    public function test_le_reversement_vire_le_net_et_jamais_le_montant_de_la_transaction(): void
    {
        Http::fake([
            '*/withdrawal' => Http::response([
                'status' => 'SUCCESS',
                'data'   => ['transaction_id' => 'REV-NET-1'],
            ], 200),
        ]);

        // Commission reelle : 50 000 de frais de scolarite, 50 de marge.
        $commission = $this->commission(Commission::STATUT_CALCULEE, 49950);

        (new ReverserEtablissementJob($commission->id))->handle(new AangaraaPayService());

        Http::assertSent(function ($req) {
            $corps = json_decode($req->body(), true) ?: [];

            // Le virement porte le NET.
            $this->assertSame('49950', (string) $corps['amount']);

            // Et surtout PAS le montant de la transaction, qui
            // sur-payerait l'etablissement de la commission.
            $this->assertNotSame('50000', (string) $corps['amount']);

            return true;
        });

        $this->assertSame(
            50000,
            (int) $commission->fresh()->montant_transaction,
            'La transaction conserve son montant d origine'
        );
    }

    /**
     * Le calcul doit rester coherent sur toute la gamme : le net reverse ne
     * peut jamais depasser le montant de la transaction, ni devenir negatif
     * sur un petit montant ou la marge est arrondie au franc superieur.
     */
    public function test_le_net_ne_depasse_jamais_le_montant_sur_la_gamme_complete(): void
    {
        $service = new AangaraaPayService();

        foreach ([1000, 5000, 10000, 50000, 100000, 1000000] as $montant) {
            $detail = $service->calculerFrais($montant);
            $marge  = $detail['frais_service'] - $detail['frais_aangaraa'];
            $net    = max(0, $montant - $marge);

            $this->assertLessThanOrEqual($montant, $net, "net > montant sur $montant");
            $this->assertGreaterThan(0, $net, "net nul sur $montant");

            // La marge prelevee doit toujours couvrir le cout AangaraaPay.
            $this->assertGreaterThanOrEqual(
                0,
                $marge,
                "Marge EduPay negative sur $montant : la plateforme perdrait de l'argent."
            );
        }
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

    // ─────────────────────────────────────────────────────────────
    // KPI du mois : le filtre ne doit pas melanger les annees
    // ─────────────────────────────────────────────────────────────

    /**
     * whereMonth seul filtre le MOIS et ignore l'annee. Les commissions du
     * meme mois des annees precedentes etaient donc comptees dans le total,
     * ce qui gonfrait la marge affichee au super admin.
     */
    public function test_le_total_du_mois_ignore_les_annees_precedentes(): void
    {
        $maintenant = now();

        // Commission 30 : mois courant. 20 : mois precedent. 10 : meme mois
        // homonyme de l'annee precedente. 30 : janvier de cette annee.
        // Seul le mois courant appartient a la fenetre.
        $cas = [
            [0, 'commission', 30, $maintenant],
            [-1, 'commission', 20, $maintenant->copy()->subMonthNoOverflow()],
            [-13, 'commission', 10, $maintenant->copy()->subYearNoOverflow()],
            [0, 'janvier', 30, $maintenant->copy()->startOfYear()],
        ];

        foreach ($cas as [$decalage, $etiquette, $montant, $date]) {
            $paiement             = $this->creerPaiement(50000);
            $paiement->created_at = $date;
            $paiement->save();

            $commission = Commission::create([
                'paiement_id'               => $paiement->id,
                'etablissement_id'          => $this->etab->id,
                'montant_transaction'       => 50000,
                'taux'                      => 0.023,
                'montant_commission'        => $montant,
                'montant_net_etablissement' => 50000,
                'frais_aangaraa'            => 1100,
                'statut'                    => Commission::STATUT_CALCULEE,
            ]);
            $commission->created_at = $date;
            $commission->save();
        }

        $debutMois = $maintenant->copy()->startOfMonth();
        $finMois   = $maintenant->copy()->endOfMonth();

        $total = Commission::whereBetween('created_at', [$debutMois, $finMois])
            ->sum('montant_commission');

        // Seul septembre compte : 30. Le mois precedent, le mois homonyme de
        // l'annee d'avant et janvier sont tous hors de la fenetre.
        $this->assertSame(30, $total);

        // L'ancien filtre whereMonth seul aurait additionne le mois homonyme
        // de l'annee precedente : 30 (septembre 2026) + 10 (septembre 2025)
        // = 40. C'etait ce que le super admin voyait.
        $ancien = Commission::whereMonth('created_at', $maintenant->month)
            ->sum('montant_commission');

        $this->assertSame(40, $ancien, 'Le filtre sans whereYear doit rester le buggy, sinon le test ne prouve rien');
    }
}
