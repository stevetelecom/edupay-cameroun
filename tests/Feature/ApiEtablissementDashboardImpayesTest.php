<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Audit G et H — contrat du tableau de bord et de la liste des impayes
 * consomme par le back-office mobile (docs/DOCUMENTATION_API.md).
 *
 * G : les compteurs etaient imbriques sous `data.kpis`, le mobile les lit a
 *     plat -> ecran a 0 et titre vide.
 * H : `data` portait un objet {synthese, frais_impayes, pagination} au lieu
 *     du tableau plat attendu -> liste des impayes vide.
 */
class ApiEtablissementDashboardImpayesTest extends TestCase
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
            'code_etablissement'     => 'ETAB' . random_int(10000, 99999),
            'nom'                   => 'Ecole Audit GH',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'admin@audit-gh.test',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => self::ANNEE_ACTIVE,
        ]);

        $this->directeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->directeur->assignRole('directeur');
    }

    private function categorie(string $nom = 'Scolarite'): CategoriesFrais
    {
        return CategoriesFrais::create([
            'etablissement_id' => $this->etablissement->id,
            'nom'              => $nom,
            'montant_total'    => 100000,
            'fractionnable'    => true,
            'nb_tranches_max'  => 2,
            'annee_scolaire'   => self::ANNEE_ACTIVE,
        ]);
    }

    private function apprenant(string $nom, string $statutPaiement, float $total, float $paye): Apprenant
    {
        $apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'nom'                      => $nom,
            'prenom'                   => 'Eleve',
            'classe'                   => 'CM2',
            'statut_paiement'          => $statutPaiement,
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => $this->categorie()->id,
            'montant_total'     => $total,
            'montant_paye'      => $paye,
            'statut'            => $paye >= $total ? 'regle' : 'partiel',
            'annee_scolaire'    => self::ANNEE_ACTIVE,
        ]);

        return $apprenant;
    }

    // ─────────────────────────────────────────────
    // Audit G — KPI a plat
    // ─────────────────────────────────────────────

    public function test_le_dashboard_expose_les_compteurs_a_plat_attendus_par_le_mobile()
    {
        $this->apprenant('Regle', 'regle', 100000, 100000);
        $this->apprenant('Partiel', 'partiel', 100000, 40000);
        $this->apprenant('Impaye', 'impaye', 100000, 0);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.dashboard'))
            ->assertOk()
            ->json('data');

        foreach ([
            'encaissements_jour', 'encaissements_mois', 'taux_recouvrement',
            'total_apprenants', 'payants', 'partiels', 'impayes',
            'transactions_recentes',
        ] as $cle) {
            $this->assertArrayHasKey($cle, $data, "La cle « {$cle} » manque au dashboard.");
        }

        $this->assertSame(3, $data['total_apprenants']);
        $this->assertSame(1, $data['payants']);
        $this->assertSame(1, $data['partiels']);
        $this->assertSame(1, $data['impayes']);
        $this->assertSame(3, $data['payants'] + $data['partiels'] + $data['impayes']);
    }

    public function test_le_dashboard_conserve_les_anciennes_cles_imbriquees()
    {
        $this->apprenant('Regle', 'regle', 100000, 100000);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.dashboard'))
            ->assertOk()
            ->json('data');

        // Le back-office web et les anciens clients lisent encore ces cles.
        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('nb_apprenants', $data['kpis']);
        $this->assertArrayHasKey('derniers_paiements', $data);
    }

    public function test_le_dashboard_calcule_les_encaissements_du_jour()
    {
        $apprenant = $this->apprenant('Regle', 'regle', 100000, 0);

        Paiement::create([
            'user_id'            => $this->directeur->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $apprenant->frais()->first()->id,
            'montant'            => 100000,
            'frais_service'      => 1500,
            'montant_total_paye' => 101500,
            'frais_aangaraa'     => 2000,
            'marge_edupay'       => 0,
            'mode_paiement'      => 'mtn_momo',
            'type_paiement'      => 'integral',
            'statut'             => 'valide',
            'telephone_paiement' => '650000000',
            'date_paiement'      => now(),
        ]);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.dashboard'))
            ->assertOk()
            ->json('data');

        $this->assertSame(100000, $data['encaissements_jour']);
        $this->assertSame(100000, $data['encaissements_mois']);
        $this->assertCount(1, $data['transactions_recentes']);
    }

    // ─────────────────────────────────────────────
    // Audit H — liste des impayes
    // ─────────────────────────────────────────────

    public function test_la_liste_des_impayes_est_un_tableau_plat_une_ligne_par_apprenant()
    {
        $impaye = $this->apprenant('Fono', 'impaye', 100000, 0);
        $this->apprenant('Regle', 'regle', 100000, 100000);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk();

        $data = $reponse->json('data');

        $this->assertIsArray($data, '`data` doit etre un tableau, pas un objet.');
        $this->assertCount(1, $data, 'Seul l\'apprenant impaye doit apparaitre.');

        $ligne = $data[0];
        $this->assertSame($impaye->id, $ligne['apprenant_id']);
        $this->assertSame('Eleve Fono', $ligne['nom']);
        $this->assertSame('CM2', $ligne['classe']);
        $this->assertSame(100000, $ligne['montant_du']);
        $this->assertArrayHasKey('dernier_paiement', $ligne);
        $this->assertArrayHasKey('derniere_relance', $ligne);
        $this->assertArrayHasKey('telephone_parent', $ligne);
    }

    public function test_le_montant_du_agregge_toutes_les_lignes_de_frais_de_l_apprenant()
    {
        $apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'nom'                      => 'Multi',
            'prenom'                   => 'Frais',
            'classe'                   => '6eme',
            'statut_paiement'          => 'partiel',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        foreach ([['Scolarite', 100000, 40000], ['Cantine', 30000, 10000]] as [$nom, $total, $paye]) {
            FraisApprenant::create([
                'apprenant_id'      => $apprenant->id,
                'categorie_frais_id' => $this->categorie($nom)->id,
                'montant_total'     => $total,
                'montant_paye'      => $paye,
                'statut'            => 'partiel',
                'annee_scolaire'    => self::ANNEE_ACTIVE,
            ]);
        }

        $ligne = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk()
            ->json('data.0');

        // (100 000 - 40 000) + (30 000 - 10 000) = 80 000
        $this->assertSame(80000, $ligne['montant_du']);
    }

    public function test_la_liste_impayes_expose_le_dernier_paiement_valide()
    {
        $apprenant = $this->apprenant('Avec', 'partiel', 100000, 40000);

        Paiement::create([
            'user_id'            => $this->directeur->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $apprenant->frais()->first()->id,
            'montant'            => 40000,
            'frais_service'      => 800,
            'montant_total_paye' => 40800,
            'frais_aangaraa'     => 800,
            'marge_edupay'       => 0,
            'mode_paiement'      => 'orange_money',
            'type_paiement'      => 'tranche',
            'statut'             => 'valide',
            'telephone_paiement' => '650000000',
            'date_paiement'      => now()->subDays(2),
        ]);

        $ligne = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertSame(40000, $ligne['dernier_paiement']['montant']);
        $this->assertNotNull($ligne['dernier_paiement']['date']);
    }

    public function test_un_apprenant_igne_est_absent_de_la_liste_des_impayes()
    {
        $this->apprenant('Ignore', 'regle', 100000, 100000);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk();

        $this->assertSame([], $reponse->json('data'));
        $this->assertSame(0, $reponse->json('pagination.total'));
    }

    public function test_la_synthese_et_la_liste_partagent_le_meme_perimetre()
    {
        $this->apprenant('A', 'impaye', 100000, 0);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk();

        $totalListe = array_sum(array_column($reponse->json('data'), 'montant_du'));
        $this->assertSame($reponse->json('synthese.total_impaye'), $totalListe);
    }
}
