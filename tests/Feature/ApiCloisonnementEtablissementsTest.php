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
 * Audit Q et R — cloisonnement inter-etablissements.
 *
 * Q : le filtre `categorie_id` de la liste des impayes n'etait pas scope a
 *     l'etablissement : une categorie d'une autre ecole pouvait etre
 *     interrogee, et les totaux de la synthese en revelaient l'existence.
 * R : le taux de recouvrement par classe etait calcule sur le filtre
 *     CLASSE seul : les frais de tous les etablissements partageant le meme
 *     nom de classe etaient additionnes (API et web).
 */
class ApiCloisonnementEtablissementsTest extends TestCase
{
    use RefreshDatabase;

    private const ANNEE = '2026-2027';

    private Etablissement $etablissement;

    private Etablissement $concurrent;

    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'directeur']);

        $this->etablissement = $this->creerEtablissement('Mon Ecole');
        $this->concurrent     = $this->creerEtablissement('Ecole Concurrente');

        $this->abonnerEtablissement($this->etablissement);
        $this->abonnerEtablissement($this->concurrent);

        $this->directeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->directeur->assignRole('directeur');
    }

    private function creerEtablissement(string $nom): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => $nom,
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => strtolower(str_replace(' ', '', $nom)) . '@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => self::ANNEE,
        ]);
    }

    private function ajouterFrais(Etablissement $etablissement, string $classe, float $total, float $paye): void
    {
        $apprenant = Apprenant::create([
            'etablissement_id'         => $etablissement->id,
            'nom'                      => 'Eleve ' . $classe,
            'prenom'                   => 'Test',
            'classe'                   => $classe,
            'statut_paiement'          => $paye >= $total ? 'regle' : 'partiel',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => CategoriesFrais::create([
                'etablissement_id' => $etablissement->id,
                'nom'              => 'Scolarite ' . $etablissement->nom,
                'montant_total'    => $total,
                'annee_scolaire'   => self::ANNEE,
            ])->id,
            'montant_total'     => $total,
            'montant_paye'      => $paye,
            'statut'            => $paye >= $total ? 'regle' : 'partiel',
            'annee_scolaire'    => self::ANNEE,
        ]);
    }

    // ─────────────────────────────────────────────
    // Q — filtre categorie_id
    // ─────────────────────────────────────────────

    public function test_le_filtre_sur_une_categorie_etrangere_est_refuse()
    {
        $this->ajouterFrais($this->etablissement, 'CM2', 100000, 0);

        $categorieEtrangere = CategoriesFrais::create([
            'etablissement_id' => $this->concurrent->id,
            'nom'              => 'Cantine concurrente',
            'montant_total'    => 50000,
            'annee_scolaire'   => self::ANNEE,
        ]);

        $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index', ['categorie_id' => $categorieEtrangere->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('categorie_id');
    }

    public function test_le_filtre_sur_une_categorie_de_son_etablissement_passe()
    {
        $this->ajouterFrais($this->etablissement, 'CM2', 100000, 0);

        $categorie = CategoriesFrais::where('etablissement_id', $this->etablissement->id)->firstOrFail();

        $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index', ['categorie_id' => $categorie->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_une_synthese_ne_rend_aucun_total_pour_une_autre_etablissement()
    {
        $this->ajouterFrais($this->concurrent, 'CM2', 900000, 0);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk();

        $this->assertSame([], $reponse->json('data'));
        $this->assertSame(0, $reponse->json('synthese.total_impaye'));
        $this->assertSame(0, $reponse->json('synthese.total_attendu'));
    }

    // ─────────────────────────────────────────────
    // R — repartition_classes
    // ─────────────────────────────────────────────

    public function test_le_taux_par_classe_ignore_les_frais_des_autres_etablissements()
    {
        // Meme classe, deux etablissements : 100 000 attendus / 100 000 payes
        // chez moi, 900 000 / 0 chez le concurrent.
        $this->ajouterFrais($this->etablissement, 'CM2', 100000, 100000);
        $this->ajouterFrais($this->concurrent, 'CM2', 900000, 0);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.rapports.index'))
            ->assertOk()
            ->json('data');

        $cm2 = collect($data['repartition_classes'])->firstWhere('nom', 'CM2');

        $this->assertNotNull($cm2);
        $this->assertSame(1, $cm2['nb_apprenants']);
        // 100 % chez moi. Avant le correctif : 100 000 / 1 000 000 = 10 %.
        $this->assertSame(100, $cm2['taux']);
    }

    public function test_le_taux_par_classe_reflete_un_paiement_partiel()
    {
        $this->ajouterFrais($this->etablissement, '6eme', 100000, 25000);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.rapports.index'))
            ->assertOk()
            ->json('data');

        $sixieme = collect($data['repartition_classes'])->firstWhere('nom', '6eme');

        $this->assertSame(25, $sixieme['taux']);
    }

    public function test_une_classe_qui_n_existe_que_chez_un_concurrent_n_apparait_pas()
    {
        $this->ajouterFrais($this->concurrent, 'Terminale', 100000, 0);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.rapports.index'))
            ->assertOk()
            ->json('data');

        $this->assertNotContains('Terminale', array_column($data['repartition_classes'], 'nom'));
        $this->assertSame(0, $data['nb_apprenants']);
    }
}
