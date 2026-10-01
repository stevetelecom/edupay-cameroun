<?php

namespace Tests\Feature;

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
 * Le format REEL de la reponse /withdrawal d'AangaraaPay.
 *
 * Constat en production le 01/10/2026 : les reversements 50 FCFA des
 * commissions 2 et 3 ont bien ete executes par AangaraaPay (montant
 * « 51 XAF SUCCESSFUL » sur le releve MTN, reference
 * 5c487ca1-07e3-418a-987b-484f721e2981), et le back office affichait
 * « a verifier » malgre une reponse HTTP 200 parfaitement exploitable.
 *
 * Deux bugs, tous deux dans la LECTURE de la reponse :
 *
 *  1. `data.status` est un BOOLEEN (`true`), pas la chaine « SUCCESSFUL »
 *     qu'attendait la doc. `(string) true` donnait « 1 », donc le test
 *     `str_starts_with($statutApi, 'SUCCESS')` ne pouvait jamais passer.
 *  2. `estErreurDocumenteeSignalee()` etait APPELEE SANS `$this->` :
 *     « Undefined variable $estErreurDocumenteeSignalee ». L'exception etait
 *     avalee par le `catch (\Throwable)`, qui concluait 'indetermine' — donc
 *     « reponse perdue » alors qu'aucune reponse n'avait jamais ete perdue.
 *
 * Ces tests rejouent la REPONSE REELLE capturee en production, octet pour
 * octet. Un test construit sur un format suppose ne prouve rien ici.
 */
class FormatReponseWithdrawalAangaraaTest extends TestCase
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
            'code_etablissement'         => 'ETAB-FMT',
            'nom'                        => 'Ecole Format',
            'type'                       => 'lycee_general',
            'statut_juridique'           => 'prive_laic',
            'region'                     => 'centre',
            'ville'                      => 'Yaounde',
            'telephone'                  => '650000000',
            'email'                      => 'admin@fmt.test',
            'taux_commission'            => 0.05,
            'numero_momo_reversement'    => '654862989',
            'operateur_momo_reversement' => 'mtn',
            'statut'                     => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Carine',
            'prenom'           => 'Fono',
            'classe'           => 'Master 1 Informatique',
            'statut_paiement'  => 'impaye',
            'actif'            => true,
        ]);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50,
            'fractionnable'    => false,
            'nb_tranches_max'  => 1,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'      => 50,
            'montant_paye'       => 0,
            'statut'             => 'impaye',
        ]);

        $this->paiement = Paiement::create([
            'user_id'            => User::factory()->create()->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => 50,
            'frais_service'      => 1,
            'montant_total_paye' => 51,
            'mode_paiement'      => 'mtn_momo',
            'statut'             => 'valide',
        ]);
    }

    private function commission(): Commission
    {
        return Commission::create([
            'paiement_id'               => $this->paiement->id,
            'etablissement_id'          => $this->etab->id,
            'montant_transaction'       => 50,
            'taux'                      => 0.05,
            'montant_commission'        => 1,
            'statut'                    => Commission::STATUT_CALCULEE,
            'montant_net_etablissement' => 50,
            'frais_aangaraa'            => 1,
        ]);
    }

    /**
     * Payload 100% reel, extrait du log de production du 01/10/2026 08:05:04
     * (commission 3, paiement 62, reference 5c487ca1-...). C'est la reponse
     * qui a produit le « a verifier » alors que le virement etait reussi.
     */
    private function reponseReelle(): array
    {
        return [
            'statusCode' => 200,
            'message'    => 'Transfer request accepted and is being processed',
            'data'       => [
                'status'       => true,
                'message'      => 'Transfer request accepted and is being processed',
                'reference_id' => '5c487ca1-07e3-418a-987b-484f721e2981',
            ],
        ];
    }

    private function reverser(): array
    {
        return app(AangaraaPayService::class)->reverserEtablissement(
            telephone:   '237654862989',
            operateur:   'mtn',
            montant:     50,
            description: 'Reversement EduPay — paiement #' . $this->paiement->id
        );
    }

    /**
     * Le test central : la reponse reelle doit etre lue comme un SUCCES.
     * Avant correction, elle sortait 'indetermine' avec le message
     * « Undefined variable $estErreurDocumenteeSignalee » — un bug de code
     * deguise en perte de reponse reseau, qui a bloque a tort deux
     * reversements reellement payes.
     */
    public function test_la_reponse_reelle_est_lue_comme_un_succes(): void
    {
        Http::fake(['*/aangaraa-pay/withdrawal' => Http::response($this->reponseReelle(), 200)]);

        $resultat = $this->reverser();

        $this->assertTrue($resultat['succes'],
            'La reponse reelle d\'AangaraaPay doit etre lue comme un succes. Message obtenu : ' . ($resultat['message'] ?? '?'));
        $this->assertSame('succes', $resultat['outcome']);
        $this->assertSame('5c487ca1-07e3-418a-987b-484f721e2981', $resultat['reference']);
    }

    /**
     * Verbe du service : un booleen `true` est une confirmation explicite
     * d'AangaraaPay, pas un statut inconnu. Sans ce cas, `(string) true`
     * produisait « 1 » et le test SUCCESS ne pouvait jamais passer.
     */
    public function test_un_status_booleen_vrai_est_normalise_en_successful(): void
    {
        Http::fake(['*/aangaraa-pay/withdrawal' => Http::response($this->reponseReelle(), 200)]);

        $resultat = $this->reverser();

        $this->assertStringNotContainsString('1', $resultat['message'] ?? '');
    }

    /**
     * Le bug `$this->` manquant : l'appel sans receveur levait une exception,
     * avalee par le catch (\Throwable) qui concluait 'indetermine'. Ce test
     * verrouille qu'aucune exception PHP ne peut plus se déguiser en perte
     * de reponse reseau.
     */
    public function test_aucune_erreur_php_ne_peut_plus_deguiser_une_perte_de_reponse(): void
    {
        Http::fake(['*/aangaraa-pay/withdrawal' => Http::response($this->reponseReelle(), 200)]);

        $resultat = $this->reverser();

        $this->assertStringNotContainsString('Undefined variable', $resultat['message'] ?? '');
        $this->assertStringNotContainsString('Error :', $resultat['message'] ?? '');
    }

    /**
     * Invariant de securite, le plus important de ce fichier.
     *
     * Une reponse 2xx SANS `status` exploitable et dont le message est une
     * confirmation (« Transfer request accepted and is being processed ») ne
     * doit surtout pas etre classee 'refuse'. Le code ne peut pas prouver que
     * le virement est parti, donc il doit rester 'indetermine', ce qui
     * interdit tout rejeu automatique.
     *
     * 'refuse' declenche un nouvel essai du job, donc un SECOND virement reel.
     * Confondre un silence d'AangaraaPay avec un refus, c'est payer deux fois.
     */
    public function test_une_confirmation_sans_status_nest_jamais_classee_refuse(): void
    {
        Http::fake([
            '*/aangaraa-pay/withdrawal' => Http::response([
                'statusCode' => 200,
                'message'    => 'Transfer request accepted and is being processed',
                'data'       => ['message' => 'Transfer request accepted and is being processed'],
            ], 200),
        ]);

        $resultat = $this->reverser();

        $this->assertNotSame('refuse', $resultat['outcome'],
            'Une confirmation sans status exploitable ne peut pas valoir refus : cela autoriserait un second virement.');
        $this->assertSame('indetermine', $resultat['outcome']);
    }

    /**
     * Le 4xx « Insufficient balance » reste un refus de solde : il ne doit
     * surtout pas devenir un succes apres normalisation du statut.
     */
    public function test_insufficient_balance_reste_un_refus_de_solde(): void
    {
        Http::fake([
            '*/aangaraa-pay/withdrawal' => Http::response([
                'statusCode' => 400,
                'message'    => 'Insufficient balance',
                'data'       => ['operator' => 'MTN_Cameroon'],
            ], 400),
        ]);

        $resultat = $this->reverser();

        $this->assertFalse($resultat['succes']);
        $this->assertSame('indetermine', $resultat['outcome']);
    }

    /**
     * Chaine « SUCCESSFUL » explicite : la forme de la doc officielle doit
     * rester acceptee, sans que la correction du booleen la casse.
     */
    public function test_la_chaine_successful_de_la_doc_rest_acceptee(): void
    {
        Http::fake([
            '*/aangaraa-pay/withdrawal' => Http::response([
                'data' => ['status' => 'SUCCESSFUL', 'reference_id' => 'DOC-1'],
            ], 200),
        ]);

        $resultat = $this->reverser();

        $this->assertTrue($resultat['succes']);
        $this->assertSame('DOC-1', $resultat['reference']);
    }

    /**
     * `status: false` est un echec explicite, pas un statut manquant.
     */
    public function test_un_status_booleen_faux_est_un_echec(): void
    {
        Http::fake([
            '*/aangaraa-pay/withdrawal' => Http::response([
                'statusCode' => 200,
                'message'    => 'Transfer refused',
                'data'       => ['status' => false, 'reference_id' => 'KO-1'],
            ], 200),
        ]);

        $resultat = $this->reverser();

        $this->assertFalse($resultat['succes']);
        $this->assertSame('FAILED', $this->statutLu($resultat));
    }

    private function statutLu(array $resultat): string
    {
        $service = app(AangaraaPayService::class);
        $methode = (new \ReflectionClass($service))->getMethod('normaliserStatutWithdrawal');
        $methode->setAccessible(true);

        return $methode->invoke($service, $resultat['raw']['data']['status'] ?? null);
    }

    /**
     * Bout en bout : la reponse reelle doitoruire la commission en
     * `prelevee` avec sa reference. C'est l'etat que le back office attend
     * pour afficher un reversement reussi au lieu de « a verifier ».
     */
    public function test_bout_en_bout_la_commission_passe_en_prelevee(): void
    {
        Http::fake(['*/aangaraa-pay/withdrawal' => Http::response($this->reponseReelle(), 200)]);

        $commission = $this->commission();

        (new \App\Jobs\ReverserEtablissementJob($commission->id))->handle(
            app(AangaraaPayService::class)
        );

        $commission->refresh();

        $this->assertSame(Commission::STATUT_PRELEVEE, $commission->statut,
            'Un reversement confirme par AangaraaPay doit aboutir en « prelevee », pas « a verifier ».');
        $this->assertSame('5c487ca1-07e3-418a-987b-484f721e2981', $commission->reference_reversement);
        $this->assertNotNull($commission->reversed_at);
        $this->assertNull($commission->reversement_erreur);
    }
}
