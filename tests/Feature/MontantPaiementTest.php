<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Echeancier;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use App\Services\AangaraaPayService;
use App\Support\MontantPaiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Audit D — le montant reellement debite doit venir du serveur.
 *
 * Regression reproduite : le mobile affichait la tranche de 25 000 FCFA
 * lue dans le calendrier de l'etablissement, POST /paiements/initier
 * ignorait `montant`, `type_paiement` et `echeancier_id`, et debitait le
 * reste du (100 000 FCFA).
 *
 * CDC §6.1 F09 : « Choix du paiement integral ou en plusieurs versements
 * selon le calendrier fixe par l'etablissement ».
 * CDC §6.2 E03 : « Definition du calendrier de paiement (date limite par
 * tranche) propre a l'etablissement ».
 */
class MontantPaiementTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────
    // Helpers de fixtures
    // ─────────────────────────────────────────────

    /**
     * Parent rattache a un apprenant valide, avec un bareme de 100 000 FCFA
     * et un calendrier de tranches (25 000 puis 75 000).
     *
     * @return array{0: User, 1: Apprenant, 2: FraisApprenant, 3: CategoriesFrais}
     */
    private function dossierAvecTranches(int $montantPaye = 0, array $tranches = [25000, 75000]): array
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'parent']);
        $user->assignRole('parent');

        $etab = Etablissement::create([
            'code_etablissement' => 'ETAB' . random_int(10000, 99999),
            'nom'               => 'Ecole Audit D',
            'type'              => 'lycee_general',
            'statut_juridique'  => 'prive_laic',
            'region'            => 'centre',
            'ville'             => 'Yaounde',
            'telephone'         => '650000000',
            'email'             => 'admin@audit-d.test',
            'taux_commission'   => 0.05,
            'statut'            => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id'         => $etab->id,
            'nom'                      => 'Eleve',
            'prenom'                   => 'Audit',
            'classe'                   => '1ere',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        $user->apprenants()->attach($apprenant->id, ['lien' => 'parent']);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etab->id,
            'nom'              => 'Frais Scolarite',
            'montant_total'    => 100000,
            'fractionnable'    => true,
            'nb_tranches_max'  => 2,
        ]);

        foreach ($tranches as $index => $montant) {
            Echeancier::create([
                'categorie_frais_id' => $categorie->id,
                'numero_tranche'     => $index + 1,
                'montant'            => $montant,
                'date_echeance'      => now()->addMonths($index + 1)->toDateString(),
                'libelle'            => 'Tranche ' . ($index + 1),
            ]);
        }

        $frais = FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'     => 100000,
            'montant_paye'      => $montantPaye,
            'statut'            => 'impaye',
        ]);

        return [$user, $apprenant, $frais, $categorie];
    }

    /**
     * Faux AangaraaPay : aucun appel reseau, on verifie seulement le montant
     * que le serveur a decide de debiter.
     */
    private function mockerAangaraaPay(): void
    {
        $mock = Mockery::mock(AangaraaPayService::class);

        $mock->shouldReceive('normaliserNumero')->andReturnUsing(
            fn (string $telephone) => (new AangaraaPayService())->normaliserNumero($telephone)
        );

        // tauxCommissionEtablissement est appele a la creation de la
        // commission pour y figer le taux reellement preleve. Le mock doit
        // donc le repondre, sinon Mockery leve sur une expectation non
        // declaree et le webhook tombe en 500.
        $mock->shouldReceive('tauxCommissionEtablissement')->andReturnUsing(
            fn ($etablissement = null) => (new AangaraaPayService())->tauxCommissionEtablissement($etablissement)
        );

        $mock->shouldReceive('calculerFrais')->andReturnUsing(function ($montant) {
            $fraisVisibles = 200;

            return [
                'montant_frais'      => $montant,
                'frais_service'      => $fraisVisibles,
                'frais_aangaraa'     => (int) round($montant * 0.02),
                'marge_edupay'       => max(0, $fraisVisibles - (int) round($montant * 0.02)),
                'montant_total_paye' => $montant + $fraisVisibles,
            ];
        });

        $mock->shouldReceive('initierPaiement')->andReturn([
            'succes'    => true,
            'pay_token' => 'MOCK_TOKEN_AUDIT_D',
            'statut'    => 'PENDING',
            'operateur' => 'MTN_Cameroon',
            'message'   => 'OK',
        ]);

        $this->app->instance(AangaraaPayService::class, $mock);
    }

    /**
     * @return array{0: \Illuminate\Testing\TestResponse, 1: FraisApprenant, 2: Apprenant}
     */
    private function initierTranche(User $user, FraisApprenant $frais, array $corps = []): array
    {
        $reponse = $this->actingAs($user, 'sanctum')->postJson(route('api.v1.paiements.initier'), array_merge([
            'frais_apprenant_id' => $frais->id,
            'type_paiement'      => 'tranche',
            'mode_paiement'      => 'mtn_momo',
            'telephone'          => '650000000',
        ], $corps));

        return [$reponse, $frais, $frais->apprenant];
    }

    // ─────────────────────────────────────────────
    // Tests HTTP — le contrat de l'API mobile
    // ─────────────────────────────────────────────

    /**
     * Le coeur du bug : la tranche affichee (25 000) doit etre la tranche
     * debitee. Avant la correction, le serveur debitait 100 000 FCFA.
     */
    public function test_la_tranche_debitee_est_celle_du_calendrier_de_l_etablissement()
    {
        $this->mockerAangaraaPay();
        [$user, , $frais, $categorie] = $this->dossierAvecTranches();
        $echeance = Echeancier::where('categorie_frais_id', $categorie->id)
            ->where('numero_tranche', 1)->firstOrFail();

        [$reponse] = $this->initierTranche($user, $frais, [
            'montant'        => 25000,
            'echeancier_id' => $echeance->id,
        ]);

        $reponse->assertStatus(201);

        $paiement = Paiement::latest('id')->first();
        $this->assertSame(25000, (int) $paiement->montant, 'Le serveur doit debiter la tranche du calendrier.');
        $this->assertSame('tranche', $paiement->type_paiement);
        $this->assertSame(1, (int) $paiement->numero_tranche);
        $this->assertSame($echeance->id, (int) $paiement->echeancier_id);
    }

    /**
     * Le montant envoye par le client ne peut pas influencer le debit,
     * meme s'il est enorme (le serveur reste la source de verite).
     */
    public function test_le_montant_envoye_par_le_client_est_ignore()
    {
        $this->mockerAangaraaPay();
        [$user, , $frais, $categorie] = $this->dossierAvecTranches();
        $echeance = Echeancier::where('categorie_frais_id', $categorie->id)
            ->where('numero_tranche', 1)->firstOrFail();

        [$reponse] = $this->initierTranche($user, $frais, [
            'montant'        => 999999,
            'echeancier_id' => $echeance->id,
        ]);

        $reponse->assertStatus(201);
        $this->assertSame(25000, (int) Paiement::latest('id')->first()->montant);
    }

    /**
     * Sans echeancier_id, le serveur prend la prochaine echeance du calendrier
     * (pas une division du reste du par nb_tranches_max).
     */
    public function test_sans_echeancier_id_le_serveur_prit_la_prochaine_echeance_du_calendrier()
    {
        $this->mockerAangaraaPay();
        [$user, , $frais] = $this->dossierAvecTranches();

        [$reponse] = $this->initierTranche($user, $frais, ['montant' => 25000]);

        $reponse->assertStatus(201);
        $this->assertSame(25000, (int) Paiement::latest('id')->first()->montant);
    }

    /**
     * Pas de sur-facturation : si le reste du est inferieur a la tranche,
     * on debite le reste du, jamais plus.
     */
    public function test_la_tranche_est_plafonnee_par_le_reste_du()
    {
        $this->mockerAangaraaPay();
        [$user, , $frais, $categorie] = $this->dossierAvecTranches(montantPaye: 90000);
        $echeance = Echeancier::where('categorie_frais_id', $categorie->id)
            ->where('numero_tranche', 1)->firstOrFail();

        [$reponse] = $this->initierTranche($user, $frais, ['echeancier_id' => $echeance->id]);

        $reponse->assertStatus(201);
        $this->assertSame(10000, (int) Paiement::latest('id')->first()->montant);
    }

    /**
     * Un echeancier appartenant a un autre bareme ne peut pas etre rattache
     * au paiement (sinon on payerait le mauvais montant).
     */
    public function test_une_echeance_d_un_autre_bareme_est_refusee()
    {
        $this->mockerAangaraaPay();
        [$user, , $frais, $categorie] = $this->dossierAvecTranches();

        $autreBareme = CategoriesFrais::create([
            'etablissement_id' => $categorie->etablissement_id,
            'nom'              => 'Cantine',
            'montant_total'    => 30000,
            'fractionnable'    => true,
            'nb_tranches_max'  => 3,
        ]);

        $echeanceEtrangere = Echeancier::create([
            'categorie_frais_id' => $autreBareme->id,
            'numero_tranche'     => 1,
            'montant'            => 10000,
            'date_echeance'      => now()->addMonth()->toDateString(),
            'libelle'            => 'Cantine T1',
        ]);

        [$reponse] = $this->initierTranche($user, $frais, ['echeancier_id' => $echeanceEtrangere->id]);

        $reponse->assertStatus(422);
        $this->assertSame(0, Paiement::count());
    }

    /**
     * Une tranche deja soldee par un paiement valide est refusee.
     */
    public function test_une_echeance_deja_payee_est_refusee()
    {
        $this->mockerAangaraaPay();
        [$user, $apprenant, $frais, $categorie] = $this->dossierAvecTranches(montantPaye: 25000);
        $echeance = Echeancier::where('categorie_frais_id', $categorie->id)
            ->where('numero_tranche', 1)->firstOrFail();

        Paiement::create([
            'user_id'            => $user->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'echeancier_id'     => $echeance->id,
            'numero_tranche'     => 1,
            'montant'            => 25000,
            'frais_service'      => 200,
            'montant_total_paye' => 25200,
            'frais_aangaraa'     => 500,
            'marge_edupay'       => 0,
            'mode_paiement'      => 'mtn_momo',
            'type_paiement'      => 'tranche',
            'statut'             => 'valide',
            'telephone_paiement' => '650000000',
            'date_paiement'      => now(),
        ]);

        [$reponse] = $this->initierTranche($user, $frais, ['echeancier_id' => $echeance->id]);

        $reponse->assertStatus(422);
        $this->assertSame(1, Paiement::count(), 'Aucun nouveau paiement ne doit etre cree.');
    }

    /**
     * Un frais deja entierement regle ne peut pas etre repaye.
     */
    public function test_un_frais_deja_solde_est_refuse()
    {
        $this->mockerAangaraaPay();
        [$user, , $frais] = $this->dossierAvecTranches(montantPaye: 100000);
        $frais->update(['statut' => 'regle']);

        [$reponse] = $this->initierTranche($user, $frais);

        $reponse->assertStatus(422);
        $this->assertSame(0, Paiement::count());
    }

    /**
     * Le paiement integral debite le reste du, et reste protege quand le
     * client envoie un montant different.
     */
    public function test_le_paiement_integral_debite_le_reste_du()
    {
        $this->mockerAangaraaPay();
        [$user, , $frais] = $this->dossierAvecTranches(montantPaye: 10000);

        $reponse = $this->actingAs($user, 'sanctum')->postJson(route('api.v1.paiements.initier'), [
            'frais_apprenant_id' => $frais->id,
            'type_paiement'      => 'integral',
            'montant'            => 25000,
            'mode_paiement'      => 'mtn_momo',
            'telephone'          => '650000000',
        ]);

        $reponse->assertStatus(201);
        $paiement = Paiement::latest('id')->first();
        $this->assertSame(90000, (int) $paiement->montant);
        $this->assertSame('integral', $paiement->type_paiement);
        $this->assertNull($paiement->numero_tranche);
    }

    /**
     * Toute somme debitee doit etre rattachee a une ligne de frais
     * (`paiements.frais_apprenant_id` est NOT NULL). Le « paiement direct »
     * renvoyait une 500 en production : il est desormais refuse avec un
     * message francais explicite.
     */
    public function test_un_paiement_sans_ligne_de_frais_est_refuse_avec_un_message_francais()
    {
        $this->mockerAangaraaPay();
        [$user, $apprenant] = $this->dossierAvecTranches();

        // Audit P : l'API respecte la langue demandee. Le message etant en
        // francais, la langue est demandee explicitement, sans quoi un client
        // envoyant Accept-Language: en recevrait la traduction anglaise.
        $reponse = $this->actingAs($user, 'sanctum')->postJson(route('api.v1.paiements.initier') . '?lang=fr', [
            'apprenant_id'  => $apprenant->id,
            'montant'       => 10000,
            'mode_paiement' => 'mtn_momo',
            'telephone'     => '650000000',
        ]);

        $reponse->assertStatus(422);
        $reponse->assertJsonPath('message', 'Veuillez choisir les frais a regler.');
        $this->assertSame(0, Paiement::count());
    }

    // ─────────────────────────────────────────────
    // Tests unitaires — la source de verite unique
    // ─────────────────────────────────────────────

    public function test_le_calcul_retourne_une_erreur_metier_explicite()
    {
        $calculateur = new MontantPaiement();
        [, , $frais] = $this->dossierAvecTranches(montantPaye: 100000);

        $resultat = $calculateur->calculer($frais->fresh(), 'tranche');

        $this->assertSame(MontantPaiement::ERREUR_DEJA_SOLDE, $resultat['erreur']);
        $this->assertSame(0, $resultat['montant']);
        $this->assertNotSame('', $calculateur->message($resultat['erreur']));
    }

    public function test_les_messages_sont_traduits_en_francais_par_defaut()
    {
        app()->setLocale('fr');
        $calculateur = new MontantPaiement();

        $this->assertSame(
            'Ces frais sont deja entierement regles.',
            $calculateur->message(MontantPaiement::ERREUR_DEJA_SOLDE)
        );

        app()->setLocale('en');
        $this->assertSame(
            'These fees are already fully paid.',
            $calculateur->message(MontantPaiement::ERREUR_DEJA_SOLDE)
        );

        app()->setLocale('fr');
    }
}
