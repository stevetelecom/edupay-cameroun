<?php

namespace Tests\Feature;

use App\Jobs\ReverserEtablissementJob;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use App\Services\AangaraaPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Les reversements « a verifier » etaient definitivement bloques.
 *
 * Constat en production le 01/10/2026 : une commission dont la reponse
 * AangaraaPay s'etait perdue partait en `a_verifier` et plus personne ne la
 * reprenait. Ni le scheduler, ni `aangaraa:reversements:rejouer` — ce dernier
 * exclut volontairement cet etat, parce qu'un rejeu peut payer deux fois.
 *
 * Deux defauts distincts, deux corrections :
 *  1. la reference AangaraaPay recue avec un PENDING etait JETEE par le job,
 *     rendant la commission invérifiable meme en principle ;
 *  2. `verifierStatutRetrait()` existait dans le service sans etre appele par
 *     le moindre code — la sortie de secours en LECTURE seule existait deja.
 */
class ReconciliationReversementsTest extends TestCase
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
            'code_etablissement'      => 'ETAB-RECO',
            'nom'                     => 'Ecole Reconciliation',
            'type'                    => 'lycee_general',
            'statut_juridique'        => 'prive_laic',
            'region'                  => 'centre',
            'ville'                   => 'Yaounde',
            'telephone'               => '650000000',
            'email'                   => 'admin@reco.test',
            'taux_commission'         => 0.05,
            'numero_momo_reversement' => '650123456',
            'operateur_momo_reversement' => 'mtn',
            'statut'                  => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Eleve',
            'prenom'           => 'Reconciliation',
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
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'      => 50000,
            'montant_paye'       => 0,
            'statut'             => 'impaye',
        ]);

        $this->paiement = Paiement::create([
            'user_id'            => User::factory()->create()->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => 50000,
            'frais_service'      => 800,
            'montant_total_paye' => 50800,
            'mode_paiement'      => 'mtn_momo',
            'statut'             => 'valide',
        ]);
    }

    private function commission(string $statut, ?string $reference = null): Commission
    {
        return Commission::create([
            'paiement_id'                => $this->paiement->id,
            'etablissement_id'           => $this->etab->id,
            'montant_transaction'        => 50000,
            'taux'                       => 0.05,
            'montant_commission'         => 800,
            'statut'                     => $statut,
            'montant_net_etablissement'  => 50000,
            'frais_aangaraa'             => 1100,
            'reference_reversement'      => $reference,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Defaut 1 : la reference PENDING etait jetee
    // ─────────────────────────────────────────────────────────────

    /**
     * Un PENDING porte une reference AangaraaPay : c'est elle qui permet, plus
     * tard, de demander a l'API si le virement a abouti. Elle etait recuperee
     * dans le resultat puis jamais ecrite en base, donc perdue.
     */
    public function test_la_reference_est_conservee_quand_aangaraa_repond_pending(): void
    {
        Http::fake([
            '*/aangaraa-pay/withdrawal' => Http::response([
                'data' => ['status' => 'PENDING', 'reference_id' => 'WITHDRAW-7788'],
            ], 200),
        ]);

        $commission = $this->commission(Commission::STATUT_CALCULEE);

        (new ReverserEtablissementJob($commission->id))->handle(
            app(AangaraaPayService::class)
        );

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);
        $this->assertSame('WITHDRAW-7788', $commission->reference_reversement,
            'La reference AangaraaPay doit etre conservee : sans elle la commission est invérifiable.');
    }

    /**
     * Un timeout ne fournit aucune reference : la colonne doit rester vide,
     * plutot que d'inventer une valeur qui ferait croire a une confirmation.
     */
    public function test_aucune_reference_inventee_lors_du_timeout(): void
    {
        Http::fake([
            '*/aangaraa-pay/withdrawal' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        $commission = $this->commission(Commission::STATUT_CALCULEE);

        (new ReverserEtablissementJob($commission->id))->handle(
            app(AangaraaPayService::class)
        );

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);
        $this->assertNull($commission->reference_reversement);
    }

    /**
     * Une reference deja enregistree ne doit jamais etre ecrasee : si une
     * tentative ulterieure se perd, seule l'ancienne reference decrit un
     * virement reellement confirme.
     */
    public function test_une_reference_existante_nest_jamais_ecrasee(): void
    {
        Http::fake([
            '*/aangaraa-pay/withdrawal' => Http::response([
                'data' => ['status' => 'PENDING', 'reference_id' => 'WITHDRAW-NOUVEAU'],
            ], 200),
        ]);

        $commission = $this->commission(Commission::STATUT_CALCULEE, 'WITHDRAW-ANCIEN');

        (new ReverserEtablissementJob($commission->id))->handle(
            app(AangaraaPayService::class)
        );

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);
        $this->assertSame('WITHDRAW-ANCIEN', $commission->reference_reversement,
            'La premiere reference, seule preuve d\'un virement confirme, doit primer.');
    }

    // ─────────────────────────────────────────────────────────────
    // Defaut 2 : personne ne tranchait le sort des « a verifier »
    // ─────────────────────────────────────────────────────────────

    /**
     * Le cas nominal : AangaraaPay confirme le virement. La commission passe
     * `prelevee` et le back office s'aligne sur la realite.
     */
    public function test_successful_passe_la_commission_en_prelevee(): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $commission = $this->commission(Commission::STATUT_A_VERIFIER, 'WITHDRAW-7788');

        $this->artisan('aangaraa:reversements:reconcilier')->assertSuccessful();

        $commission->refresh();

        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->statut);
        $this->assertNotNull($commission->reversed_at);
    }

    /**
     * FAILED confirme qu'aucun argent n'est parti : la commission redevient
     * `calculee`, donc rejouable par le filet normal. C'est la seule voie par
     * laquelle cette commande rend de l'argent eligible a un envoi, et elle ne
     * le fait qu'apres preuve de non-envoi.
     */
    public function test_failed_repasse_la_commission_en_calculee(): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response(['status' => 'FAILED'], 200),
        ]);

        $commission = $this->commission(Commission::STATUT_A_VERIFIER, 'WITHDRAW-7788');

        $this->artisan('aangaraa:reversements:reconcilier')->assertSuccessful();

        $commission->refresh();

        $this->assertSame(Commission::STATUT_CALCULEE, $commission->statut);
    }

    /**
     * Le point de securite central : la commande NE DOIT JAMAIS appeler
     * `/withdrawal`. Elle n'a pas le droit de deplacer d'argent, sous aucun
     * etat. Sans ce test, une refonte future pourrait transformer la
     * reconciliation en rejeu.
     */
    public function test_la_reconciliation_envoie_jamais_dargent(): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
            '*/aangaraa-pay/withdrawal'   => Http::response([
                'data' => ['status' => 'SUCCESSFUL', 'reference_id' => 'ENVOI-INTERDIT'],
            ], 200),
        ]);

        $this->commission(Commission::STATUT_A_VERIFIER, 'WITHDRAW-7788');

        $this->artisan('aangaraa:reversements:reconcilier')->assertSuccessful();

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/withdrawal');
        });
    }

    /**
     * PENDING ne prouve rien : ni depart, ni echec. On ne suppose rien et la
     * commission reste reconciliable au passage suivant.
     */
    public function test_pending_ne_decide_rien(): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response(['status' => 'PENDING'], 200),
        ]);

        $commission = $this->commission(Commission::STATUT_A_VERIFIER, 'WITHDRAW-7788');

        $this->artisan('aangaraa:reversements:reconcilier')->assertSuccessful();

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);
    }

    /**
     * Sans reference, il n'y a rien contre quoi interroger l'API. La commande
     * ne devine pas : elle laisse la commission en place et signale le cas.
     */
    public function test_sans_reference_aucun_appel_api_et_statut_inchange(): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $commission = $this->commission(Commission::STATUT_A_VERIFIER, null);

        $this->artisan('aangaraa:reversements:reconcilier')
            ->expectsOutputToContain('INTERVENTION HUMAINE')
            ->assertSuccessful();

        $commission->refresh();

        $this->assertSame(Commission::STATUT_A_VERIFIER, $commission->statut);

        Http::assertNothingSent();
    }

    /**
     * Les commissions dans d'autres etats ne sont jamais touchees : le filet
     * `calculee` et les reversements deja preleves ne doivent pas bouger.
     */
    public function test_les_autres_commissions_ne_sont_touchees_pas(): void
    {
        Http::fake([
            '*/check_withdrawal_status/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        $prelevee = $this->commission(Commission::STATUT_PRELEVEE, 'REF-OK');

        $this->artisan('aangaraa:reversements:reconcilier')->assertSuccessful();

        $prelevee->refresh();

        $this->assertSame(Commission::STATUT_PRELEVEE, $prelevee->statut);
    }
}
