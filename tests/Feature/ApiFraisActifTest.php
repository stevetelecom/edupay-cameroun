<?php

namespace Tests\Feature;

use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le champ `actif` d'une catégorie de frais était renvoyé par l'API
 * (formaterCategorie) mais ni validé ni enregistré par `update` : le mobile
 * pouvait lire l'état, jamais le changer — alors que `destroy` renvoie 422 en
 * conseillant explicitement de « la désactiver ». Le web, lui, le basculait.
 */
class ApiFraisActifTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etablissement;

    private User $directeur;

    private CategoriesFrais $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('directeur', 'web');

        $this->etablissement = Etablissement::create([
            'code_etablissement'     => 'ACT-2026',
            'nom'                   => 'Ecole Actif',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000001',
            'email'                 => 'actif@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);

            $this->abonnerEtablissement($this->etablissement);

        $this->directeur = User::factory()->create(['etablissement_id' => $this->etablissement->id]);
        $this->directeur->assignRole('directeur');

        $this->categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etablissement->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'actif'            => true,
            'annee_scolaire'   => '2026-2027',
        ]);
    }

    private function payload(array $extras = []): array
    {
        return array_merge([
            'nom'             => 'Scolarite',
            'montant_total'   => 50000,
            'nb_tranches_max' => 1,
            'annee_scolaire'  => '2026-2027',
        ], $extras);
    }

    public function test_la_categorie_expose_actif_en_lecture()
    {
        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.frais.index'));

        $reponse->assertOk();
        $this->assertTrue($reponse->json('data.0.actif'));
    }

    public function test_actif_peut_etre_desactive_depuis_lapi()
    {
        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->putJson(route('api.v1.etablissement.frais.update', $this->categorie), $this->payload(['actif' => false]));

        $reponse->assertOk();
        $this->assertFalse($reponse->json('data.actif'));
        $this->assertFalse($this->categorie->fresh()->actif);
    }

    public function test_actif_peut_etre_reactive()
    {
        $this->categorie->update(['actif' => false]);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->putJson(route('api.v1.etablissement.frais.update', $this->categorie), $this->payload(['actif' => true]));

        $reponse->assertOk();
        $this->assertTrue($reponse->json('data.actif'));
        $this->assertTrue($this->categorie->fresh()->actif);
    }

    public function test_actif_absent_de_la_payload_conserve_la_valeur()
    {
        $this->categorie->update(['actif' => false]);

        $this->actingAs($this->directeur, 'sanctum')
            ->putJson(route('api.v1.etablissement.frais.update', $this->categorie), $this->payload())
            ->assertOk();

        $this->assertFalse($this->categorie->fresh()->actif, 'un update sans `actif` ne doit pas réactiver la catégorie');
    }

    public function test_actif_non_booleen_est_refuse()
    {
        $this->actingAs($this->directeur, 'sanctum')
            ->putJson(route('api.v1.etablissement.frais.update', $this->categorie), $this->payload(['actif' => 'peut-etre']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('actif');

        $this->assertTrue($this->categorie->fresh()->actif);
    }

    public function test_store_peut_creer_une_categorie_desactivee()
    {
        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.frais.store'), $this->payload(['actif' => false]));

        $reponse->assertCreated();
        $this->assertFalse($reponse->json('data.actif'));
        $this->assertFalse(CategoriesFrais::find($reponse->json('data.id'))->actif);
    }

    public function test_store_sans_actif_cree_une_categorie_active()
    {
        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.frais.store'), $this->payload());

        $reponse->assertCreated();
        $this->assertTrue($reponse->json('data.actif'));
    }

    public function test_un_autre_etablissement_ne_peut_pas_bascule_actif()
    {
        $autre = Etablissement::create([
            'code_etablissement'     => 'AUT-2026',
            'nom'                   => 'Ecole Autre',
            'type'                  => 'college',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Douala',
            'telephone'             => '650000002',
            'email'                 => 'autre@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);

        $this->abonnerEtablissement($autre);

        $directeurAutre = User::factory()->create(['etablissement_id' => $autre->id]);
        $directeurAutre->assignRole('directeur');

        $this->actingAs($directeurAutre, 'sanctum')
            ->putJson(route('api.v1.etablissement.frais.update', $this->categorie), $this->payload(['actif' => false]))
            ->assertStatus(403);

        $this->assertTrue($this->categorie->fresh()->actif);
    }
}
