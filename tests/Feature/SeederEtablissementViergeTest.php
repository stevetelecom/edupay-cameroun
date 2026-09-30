<?php

namespace Tests\Feature;

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Vérifie que `ProdCoreAccountsSeeder` fonctionne sur une base où l'établissement
 * UD-2026 n'existe pas encore.
 *
 * Le bug qu'attrape ce test : en développement, UD-2026 existait déjà (id 2), le
 * seeder ne tentait donc jamais l'insertion et le défaut passait inaperçu. En
 * production, l'établissement manquait et le seeder échouait sur la première
 * colonne NOT NULL sans valeur par défaut. Le message ne citait qu'`email`
 * alors que `type`, `statut_juridique` et `region` manquaient aussi : chaque
 * correction successive aurait révélé la suivante.
 *
 * Le test part donc d'une base vide et exige que les cinq objets de
 * démonstration soient créés sans erreur.
 */
class SeederEtablissementViergeTest extends TestCase
{
    use RefreshDatabase;

    private function executerSeeder(): void
    {
        // Les rôles Spatie sont créés par `DatabaseSeeder` et existent déjà en
        // production. On les pose ici pour que le test se place dans la même
        // situation : celle d'une base qui a les rôles mais pas encore les
        // comptes de démonstration.
        foreach (['admin', 'directeur', 'eleve', 'parent'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->seed(\Database\Seeders\ProdCoreAccountsSeeder::class);
    }

    public function test_le_seeder_cree_l_etablissement_quand_il_est_absent(): void
    {
        $this->assertSame(0, Etablissement::count());

        $this->executerSeeder();

        $etablissement = Etablissement::where('code_etablissement', 'UD-2026')->first();

        $this->assertNotNull($etablissement, 'UD-2026 doit être créé sur une base vierge');
        $this->assertSame('Université de Douala', $etablissement->nom);
        $this->assertSame('actif', $etablissement->statut);
    }

    /**
     * Colonnes NOT NULL sans valeur par défaut dans la migration de création.
     * Si l'une d'elles manque dans le seeder, l'insertion échoue en MySQL —
     * SQLite, plus laxiste, laisse passer une partie de ces cas.
     */
    public function test_toutes_les_colonnes_obligatoires_sont_fournies(): void
    {
        $this->executerSeeder();

        $etablissement = Etablissement::where('code_etablissement', 'UD-2026')->first();

        foreach ([
            'nom',
            'type',
            'statut_juridique',
            'region',
            'ville',
            'telephone',
            'email',
        ] as $colonne) {
            $this->assertNotEmpty(
                $etablissement->{$colonne},
                "La colonne obligatoire `{$colonne}` ne doit pas être vide"
            );
        }

        $this->assertSame('universite', $etablissement->type);
        $this->assertSame('public', $etablissement->statut_juridique);
        $this->assertSame('littoral', $etablissement->region);
        $this->assertSame('contact@univ-douala.cm', $etablissement->email);
    }

    public function test_le_seeder_cree_les_deux_comptes_et_l_abonnement(): void
    {
        $this->assertSame(0, User::count());

        $this->executerSeeder();

        $carine = User::where('email', 'bebemakany@gmail.com')->first();
        $this->assertNotNull($carine, 'Carine FONO doit être créée sur une base vierge');
        $this->assertSame('Carine', $carine->prenom);
        $this->assertSame('FONO', $carine->nom);
        $this->assertSame('654862989', $carine->telephone);
        $this->assertTrue($carine->hasRole('eleve'));

        $directeur = User::where('email', 'bebewandji2@gmail.com')->first();
        $this->assertNotNull($directeur, 'Paul ATEBA doit être créé sur une base vierge');
        $this->assertTrue($directeur->hasRole('directeur'));

        $this->assertDatabaseHas('abonnements', [
            'etablissement_id' => $etablissementId = Etablissement::where('code_etablissement', 'UD-2026')->value('id'),
            'statut'           => 'actif',
        ]);
    }

    /**
     * Un second passage ne doit rien dupliquer : le seeder tourne à chaque
     * déploiement via GitHub Actions.
     */
    public function test_le_seeder_est_idempotent(): void
    {
        $this->executerSeeder();
        $this->executerSeeder();

        $this->assertSame(1, Etablissement::where('code_etablissement', 'UD-2026')->count());
        $this->assertSame(1, User::where('email', 'bebemakany@gmail.com')->count());
        $this->assertSame(1, User::where('email', 'bebewandji2@gmail.com')->count());
    }
}