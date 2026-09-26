<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit T — rattachement d'un apprenant par un payeur.
 *
 * Deux failles :
 *  - aucune regle n'etait obligatoire : une requete vide atteignait
 *    `Apprenant::query()` sans filtre et l'API renvoyait les 20 premiers
 *    eleves de toute la plateforme ;
 *  - `etablissement_id`, present au contrat documente, n'etait ni valide ni
 *    lu : seule la recherche par nom sans etablissement aboutissait, donc
 *    hors de l'ecole cible.
 */
class ApiRattachementApprenantTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $monEcole;

    private Etablissement $autreEcole;

    private User $payeur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monEcole   = $this->creerEtablissement('Mon Ecole', 'MON-2026');
        $this->autreEcole = $this->creerEtablissement('Autre Ecole', 'AUT-2026');

        $this->creerApprenant($this->monEcole, 'EP-1184', 'FONO', 'Brice', '3eme');
        $this->creerApprenant($this->monEcole, 'EP-1185', 'FONO', 'Alice', '3eme');
        $this->creerApprenant($this->autreEcole, 'AUT-0001', 'TAPANG', 'Paul', 'Terminale');

        $this->payeur = User::factory()->create();
    }

    private function creerEtablissement(string $nom, string $code): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => $code,
            'nom'                   => $nom,
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => strtolower(str_replace(' ', '', $nom)) . '@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);
    }

    private function creerApprenant(Etablissement $e, string $matricule, string $nom, string $prenom, string $classe): Apprenant
    {
        return Apprenant::create([
            'etablissement_id'         => $e->id,
            'matricule'                => $matricule,
            'nom'                      => $nom,
            'prenom'                   => $prenom,
            'classe'                   => $classe,
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
    }

    // ─────────────────────────────────────────────
    // Isolation
    // ─────────────────────────────────────────────

    public function test_une_requete_vide_ne_liste_plus_les_eleves_de_la_plateforme()
    {
        $reponse = $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [])
            ->assertStatus(422);

        $this->assertArrayHasKey('code_etablissement', $reponse->json('errors'));
        $this->assertNull($reponse->json('data'));
    }

    public function test_une_recherche_sans_critere_est_refusee()
    {
        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nom');
    }

    public function test_une_recherche_par_nom_ne_traverse_pas_les_autres_ecoles()
    {
        // « Paul » n'existe que dans l'autre ecole : la recherche ciblee sur
        // mon ecole ne doit rien retourner.
        $reponse = $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
                'nom'                => 'TAPANG',
            ])
            ->assertOk();

        $this->assertSame([], $reponse->json('data'));
    }

    public function test_un_matricule_d_une_autre_ecole_n_est_pas_trouve()
    {
        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
                'matricule'          => 'AUT-0001',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('matricule');
    }

    public function test_un_code_etablissement_inconnu_est_refuse()
    {
        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'INCONNU-000',
                'matricule'          => 'EP-1184',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code_etablissement');
    }

    public function test_un_etablissement_non_actif_accepte_pas_de_nouveau_rattachement()
    {
        $this->autreEcole->update(['statut' => 'suspendu']);

        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'AUT-2026',
                'matricule'          => 'AUT-0001',
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('user_apprenant', [
            'user_id'      => $this->payeur->id,
            'apprenant_id' => $this->creerApprenant($this->monEcole, 'EP-9', 'Zoua', 'Zita', 'CM1')->id,
        ]);
    }

    // ─────────────────────────────────────────────
    // Contrat
    // ─────────────────────────────────────────────

    public function test_le_rattachement_par_matricule_repond_au_contrat_documente()
    {
        $data = $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
                'matricule'          => 'EP-1184',
                'lien'               => 'parent',
            ])
            ->assertCreated()
            ->json('data');

        $this->assertSame('EP-1184', $data['matricule']);
        $this->assertSame('Brice FONO', $data['nom_complet']);
        $this->assertSame('Mon Ecole', $data['etablissement']);
        $this->assertSame('3eme', $data['classe']);
        $this->assertSame('impaye', $data['statut_paiement']);
        $this->assertArrayHasKey('apprenant_id', $data);

        $apprenantId = $data['apprenant_id'];

        $this->assertDatabaseHas('user_apprenant', [
            'user_id'      => $this->payeur->id,
            'apprenant_id' => $apprenantId,
            'lien'         => 'parent',
        ]);
    }

    public function test_le_rattachement_accepte_etablissement_id_comme_le_code()
    {
        $reponse = $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'etablissement_id' => $this->monEcole->id,
                'nom'             => 'FONO',
            ])
            ->assertOk();

        // Deux eleves portent ce nom dans cette ecole : la selection est
        // renvoyee sans rattachement automatique.
        $this->assertCount(2, $reponse->json('data'));
        $this->assertStringContainsString('Plusieurs', $reponse->json('message'));

        $this->assertDatabaseMissing('user_apprenant', ['user_id' => $this->payeur->id]);
    }

    public function test_un_etablissement_id_inexistant_est_refuse()
    {
        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'etablissement_id' => 999999,
                'matricule'        => 'EP-1184',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('etablissement_id');
    }

    public function test_un_double_rattachement_est_signale_sans_dupliquer_la_liaison()
    {
        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
                'matricule'          => 'EP-1184',
            ])
            ->assertCreated();

        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
                'matricule'          => 'EP-1184',
            ])
            ->assertOk();

        $this->assertSame(1, $this->payeur->apprenants()->count());
    }

    public function test_un_eleve_peut_etre_rattache_comme_soi_meme()
    {
        $data = $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
                'matricule'          => 'EP-1185',
                'lien'               => 'soi-meme',
            ])
            ->assertCreated()
            ->json('data');

        $this->assertDatabaseHas('user_apprenant', [
            'user_id'      => $this->payeur->id,
            'apprenant_id' => $data['apprenant_id'],
            'lien'         => 'soi-meme',
        ]);
    }

    public function test_un_lien_inconnu_est_refuse()
    {
        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher'), [
                'code_etablissement' => 'MON-2026',
                'matricule'          => 'EP-1184',
                'lien'               => 'tuteur',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lien');
    }
}
