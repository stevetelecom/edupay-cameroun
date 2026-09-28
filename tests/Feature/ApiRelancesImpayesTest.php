<?php

namespace Tests\Feature;

use App\Mail\RelanceImpayeMail;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\RelanceImpaye;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Audit U — protection des relances d'impayes.
 *
 * Avant, aucune trace des relances : l'endpoint pouvait etre appele en boucle
 * et le meme parent recevait la meme relance indefiniment, et
 * `derniere_relance` (contrat mobile) valait toujours null.
 */
class ApiRelancesImpayesTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etablissement;

    private Etablissement $autreEtablissement;

    private User $directeur;

    private User $parent;

    private Apprenant $apprenant;

    private FraisApprenant $frais;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('directeur', 'web');
        Role::findOrCreate('parent', 'web');

        $this->etablissement = $this->creerEtablissement('Ecole Relance', 'REL-2026');
        $this->autreEtablissement = $this->creerEtablissement('Ecole Suivante', 'SUIV-2026');

        $this->abonnerEtablissement($this->etablissement);
        $this->abonnerEtablissement($this->autreEtablissement);

        $this->directeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->directeur->assignRole('directeur');

        $this->parent = User::factory()->create([
            'email'            => 'parent.relance@test.cm',
            'notif_email'      => true,
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->parent->assignRole('parent');

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etablissement->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'actif'            => true,
            'annee_scolaire'   => $this->etablissement->annee_scolaire_active,
        ]);

        $this->apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'matricule'                => 'REL-0001',
            'nom'                      => 'Mbarga',
            'prenom'                   => 'Yves',
            'classe'                   => '4eme',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
        $this->apprenant->parents()->attach($this->parent->id, ['lien' => 'parent']);

        $this->frais = FraisApprenant::create([
            'apprenant_id'     => $this->apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'annee_scolaire'   => $this->etablissement->annee_scolaire_active,
            'montant_total'    => 50000,
            'montant_paye'     => 10000,
            'statut'           => 'partiel',
        ]);
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

    private function routeRelanceGroupe(): string
    {
        return route('api.v1.etablissement.impayes.relancer');
    }

    // ─────────────────────────────────────────────
    // Anti-spam
    // ─────────────────────────────────────────────

    public function test_la_premiere_relance_est_envoyee_et_tracee()
    {
        Mail::fake();

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe())
            ->assertOk();

        $this->assertSame(1, $reponse->json('envoi.envoyes'));
        $this->assertSame(0, $reponse->json('envoi.ignores'));
        Mail::assertSent(RelanceImpayeMail::class, 1);

        $this->assertDatabaseHas('relances_impayes', [
            'frais_apprenant_id' => $this->frais->id,
            'apprenant_id'       => $this->apprenant->id,
            'etablissement_id'   => $this->etablissement->id,
            'user_id'            => $this->parent->id,
            'canal'              => 'email',
            'statut'             => 'envoye',
        ]);
    }

    public function test_une_seconde_relance_immediate_est_refusee_avec_429()
    {
        Mail::fake();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe())
            ->assertOk();

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe())
            ->assertStatus(429);

        $this->assertSame(1, $reponse->json('envoi.ignores'));
        $this->assertStringContainsString('force=true', $reponse->json('message'));

        // Un seul email part au total : le parent n'est pas spamme.
        Mail::assertSent(RelanceImpayeMail::class, 1);
    }

    public function test_force_permet_de_renvoyer_une_relance()
    {
        Mail::fake();

        $this->actingAs($this->directeur, 'sanctum')->postJson($this->routeRelanceGroupe())->assertOk();
        $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe(), ['force' => true])
            ->assertOk();

        Mail::assertSent(RelanceImpayeMail::class, 2);
    }

    public function test_apres_le_delai_anti_spam_une_nouvelle_relance_est_autorisee()
    {
        Mail::fake();

        $this->actingAs($this->directeur, 'sanctum')->postJson($this->routeRelanceGroupe())->assertOk();

        // On repousse la relance de 25 h dans le passe.
        RelanceImpaye::where('statut', 'envoye')->update([
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ]);

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe())
            ->assertOk();

        Mail::assertSent(RelanceImpayeMail::class, 2);
    }

    public function test_une_relance_echouee_ne_bloque_pas_la_suivante()
    {
        Mail::fake();

        // Parent sans email : echec trace, donc pas d'anti-spam.
        $this->parent->update(['email' => null, 'telephone' => '650000001']);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe())
            ->assertStatus(422);

        $this->assertSame(1, $reponse->json('envoi.echecs'));
        $this->assertDatabaseHas('relances_impayes', [
            'frais_apprenant_id' => $this->frais->id,
            'statut'             => 'echec',
        ]);

        // Le parent reactive ses notifications : la relance repart.
        $this->parent->update(['email' => 'parent.relance@test.cm']);

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe())
            ->assertOk();

        Mail::assertSent(RelanceImpayeMail::class, 1);
    }

    public function test_une_frais_reglee_ne_declenche_aucune_relance()
    {
        Mail::fake();

        $this->frais->update(['montant_paye' => 50000, 'statut' => 'regle']);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson($this->routeRelanceGroupe())
            ->assertStatus(422);

        $this->assertSame(0, $reponse->json('envoi.envoyes'));
        $this->assertDatabaseCount('relances_impayes', 0);
    }

    // ─────────────────────────────────────────────
    // Contrat : derniere_relance
    // ─────────────────────────────────────────────

    public function test_la_liste_des_impayes_expose_la_derniere_relance_reussie()
    {
        Mail::fake();

        $avant = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk()
            ->json('data.0.derniere_relance');

        $this->assertNull($avant);

        $this->actingAs($this->directeur, 'sanctum')->postJson($this->routeRelanceGroupe())->assertOk();

        $apres = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.impayes.index'))
            ->assertOk()
            ->json('data.0.derniere_relance');

        $this->assertNotNull($apres);
        $this->assertSame('email', $apres['canal']);
        $this->assertNotNull($apres['date']);
    }

    public function test_une_relance_en_echec_ne_remplit_pas_derniere_relance()
    {
        $this->parent->update(['email' => null, 'telephone' => '650000001']);

        $this->actingAs($this->directeur, 'sanctum')->postJson($this->routeRelanceGroupe())->assertStatus(422);

        $this->assertNull(
            $this->actingAs($this->directeur, 'sanctum')
                ->getJson(route('api.v1.etablissement.impayes.index'))
                ->assertOk()
                ->json('data.0.derniere_relance')
        );
    }

    // ─────────────────────────────────────────────
    // Cloisonnement
    // ─────────────────────────────────────────────

    public function test_une_relance_ciblee_est_bloquee_chez_un_autre_etablissement()
    {
        $directeurAutre = User::factory()->create([
            'etablissement_id' => $this->autreEtablissement->id,
        ]);
        $directeurAutre->assignRole('directeur');

        $this->actingAs($directeurAutre, 'sanctum')
            ->postJson(route('api.v1.etablissement.impayes.relancerApprenant', $this->apprenant))
            ->assertForbidden();
    }

    public function test_les_relances_sont_tracees_par_etablissement()
    {
        Mail::fake();

        $this->actingAs($this->directeur, 'sanctum')->postJson($this->routeRelanceGroupe())->assertOk();

        $this->assertDatabaseHas('relances_impayes', [
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->assertDatabaseMissing('relances_impayes', [
            'etablissement_id' => $this->autreEtablissement->id,
        ]);
    }
}
