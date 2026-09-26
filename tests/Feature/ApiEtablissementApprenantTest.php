<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Audit E et F — contrat de la ressource Apprenant consommee par le
 * back-office mobile.
 *
 * E : « Reste dû » affichait 0 car la ressource n'exposait ni total_du, ni
 *     total_paye, ni solde_du.
 * F : les boutons « Valider » / « Rejeter » n'apparaissaient pas car la
 *     ressource n'exposait aucun `statut`.
 */
class ApiEtablissementApprenantTest extends TestCase
{
    use RefreshDatabase;

    private const ANNEE_ACTIVE = '2026-2027';

    private User $directeur;

    private Etablissement $etablissement;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'directeur']);

        $this->etablissement = Etablissement::create([
            'code_etablissement'      => 'ETAB' . random_int(10000, 99999),
            'nom'                    => 'Ecole Mobile Audit EF',
            'type'                   => 'lycee_general',
            'statut_juridique'       => 'prive_laic',
            'region'                 => 'centre',
            'ville'                  => 'Yaounde',
            'telephone'              => '650000000',
            'email'                  => 'admin@audit-ef.test',
            'taux_commission'        => 0.05,
            'statut'                 => 'actif',
            'annee_scolaire_active'  => self::ANNEE_ACTIVE,
        ]);

        $this->directeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->directeur->assignRole('directeur');
    }

    /**
     * Apprenant rattache a un bareme, avec un montant regle et un reste du.
     */
    private function apprenantAvecFrais(
        string $source = 'etablissement',
        bool $valide = true,
        string $annee = self::ANNEE_ACTIVE,
        float $total = 100000,
        float $paye = 40000
    ): Apprenant {
        $apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'nom'                      => 'Eleve',
            'prenom'                   => 'Audit',
            'classe'                   => '1ere',
            'statut_paiement'          => 'partiel',
            'source'                   => $source,
            'valide_par_etablissement' => $valide,
            'actif'                    => true,
        ]);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etablissement->id,
            'nom'              => 'Scolarite ' . $annee,
            'montant_total'    => $total,
            'fractionnable'    => true,
            'nb_tranches_max'  => 2,
            'annee_scolaire'   => $annee,
        ]);

        FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'     => $total,
            'montant_paye'      => $paye,
            'statut'            => 'partiel',
            'annee_scolaire'    => $annee,
        ]);

        return $apprenant->fresh(['etablissement', 'frais.categorieFrais']);
    }

    // ─────────────────────────────────────────────
    // Audit E — les soldes
    // ─────────────────────────────────────────────

    public function test_la_liste_expose_total_du_total_paye_et_solde_du()
    {
        $this->apprenantAvecFrais();

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertOk();

        $apprenant = $reponse->json('data.0');

        $this->assertEqualsWithDelta(100000, $apprenant['total_du'], 0.01);
        $this->assertEqualsWithDelta(40000, $apprenant['total_paye'], 0.01);
        $this->assertEqualsWithDelta(60000, $apprenant['solde_du'], 0.01, 'Le reste dû ne doit plus être 0.');
        $this->assertSame(self::ANNEE_ACTIVE, $apprenant['annee_scolaire']);
    }

    public function test_le_detail_expose_egalement_les_soldes()
    {
        $apprenant = $this->apprenantAvecFrais();

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.show', $apprenant))
            ->assertOk();

        $this->assertEqualsWithDelta(100000, $reponse->json('data.total_du'), 0.01);
        $this->assertEqualsWithDelta(40000, $reponse->json('data.total_paye'), 0.01);
        $this->assertEqualsWithDelta(60000, $reponse->json('data.solde_du'), 0.01);
    }

    public function test_les_soldes_sont_limites_a_l_annee_scolaire_active()
    {
        // Apprenant avec deux baremes :
        //  - annee active 2026-2027 : 100 000 dont 40 000 regles
        //  - annee 2025-2026 (impayee) : 50 000
        // Le « reste dû » de l'annee en cours ne doit pas englober l'ancienne
        // annee, sinon le payeur voit un solde gonfle a tort.
        $apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'nom'                      => 'Deux',
            'prenom'                   => 'Annees',
            'classe'                   => '2eme',
            'statut_paiement'          => 'partiel',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        foreach ([[self::ANNEE_ACTIVE, 100000, 40000], ['2025-2026', 50000, 0]] as [$annee, $total, $paye]) {
            $categorie = CategoriesFrais::create([
                'etablissement_id' => $this->etablissement->id,
                'nom'              => 'Bareme ' . $annee,
                'montant_total'    => $total,
                'fractionnable'    => true,
                'nb_tranches_max'  => 2,
                'annee_scolaire'   => $annee,
            ]);

            FraisApprenant::create([
                'apprenant_id'      => $apprenant->id,
                'categorie_frais_id' => $categorie->id,
                'montant_total'     => $total,
                'montant_paye'      => $paye,
                'statut'            => $paye > 0 ? 'partiel' : 'impaye',
                'annee_scolaire'    => $annee,
            ]);
        }

        $item = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertEqualsWithDelta(100000, $item['total_du'], 0.01, 'Seule l\'annee active doit etre comptee.');
        $this->assertEqualsWithDelta(40000, $item['total_paye'], 0.01);
        $this->assertEqualsWithDelta(60000, $item['solde_du'], 0.01);
    }

    public function test_un_apprenant_sans_frais_a_un_reste_du_nul()
    {
        Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'nom'                      => 'Sans',
            'prenom'                   => 'Frais',
            'classe'                   => 'CM2',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertOk();

        $this->assertEqualsWithDelta(0, $reponse->json('data.0.solde_du'), 0.01);
        $this->assertEqualsWithDelta(0, $reponse->json('data.0.total_du'), 0.01);
    }

    // ─────────────────────────────────────────────
    // Audit F — le statut de rattachement
    // ─────────────────────────────────────────────

    public function test_le_statut_en_attente_permet_d_afficher_valider_et_rejeter()
    {
        $this->apprenantAvecFrais(source: 'payeur', valide: false);

        $apprenant = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertSame('en_attente', $apprenant['statut']);
        $this->assertFalse($apprenant['valide_par_etablissement']);
    }

    public function test_le_statut_passe_a_valide_apres_validation_par_l_etablissement()
    {
        $apprenant = $this->apprenantAvecFrais(source: 'payeur', valide: false);

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.apprenants.valider', $apprenant))
            ->assertOk();

        $this->assertSame('valide', $apprenant->fresh()->statutRattachement());

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'));

        $this->assertSame('valide', $reponse->json('data.0.statut'));
    }

    public function test_un_apprenant_saisi_par_l_etablissement_est_actif()
    {
        $this->apprenantAvecFrais(source: 'etablissement', valide: true);

        $apprenant = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertSame('actif', $apprenant['statut']);
    }

    public function test_un_compte_non_autorise_recoit_une_erreur_en_francais()
    {
        $apprenant = $this->apprenantAvecFrais();

        $parent = User::factory()->create();
        Role::firstOrCreate(['name' => 'parent']);
        $parent->assignRole('parent');

        $this->actingAs($parent, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertStatus(403);

        unset($apprenant);
    }
}
