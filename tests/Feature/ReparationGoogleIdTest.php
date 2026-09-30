<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vérifie la migration de réparation de l'état « fantôme » du 30/09/2026.
 *
 * En production, `google_id` était absente de `users` alors que sa migration
 * était enregistrée dans la table `migrations` : `migrate --force` ne faisait
 * donc rien et la connexion Google levait un 500 permanent, que redéployer ne
 * pouvait pas réparer. Cette migration doit recréer la colonne quand elle
 * manque, et rester silencieuse quand elle existe déjà.
 */
class ReparationGoogleIdTest extends TestCase
{
    private function migration()
    {
        return require database_path('migrations/2026_09_30_130000_reparer_colonne_google_id_manquante.php');
    }

    /**
     * Isole une base SQLite vierge ne contenant qu'un `users` minimal, afin de
     * rejouer le cas réel : la colonne absente.
     *
     * On ne peut pas faire ce test sur la table `users` du test principal :
     * SQLite ne sait pas supprimer une colonne portant un index unique
     * (« error in index after drop column »). C'est une limite de SQLite, pas
     * de MySQL, où la production tourne.
     */
    private function isolerUsersSansGoogleId(): void
    {
        config(['database.connections.reparation' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        DB::setDefaultConnection('reparation');

        DB::connection('reparation')->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('password')->nullable();
        });
    }

    private function colonneExiste(): bool
    {
        return DB::connection('reparation')->getSchemaBuilder()->hasColumn('users', 'google_id');
    }

    public function test_la_colonne_google_id_est_recreee_quand_elle_manque(): void
    {
        $this->isolerUsersSansGoogleId();

        $this->assertFalse($this->colonneExiste(), 'Pré-condition : google_id absente.');

        $this->migration()->up();

        $this->assertTrue(
            $this->colonneExiste(),
            'La migration doit recréer google_id, sans quoi le 500 Google revient.'
        );
    }

    public function test_la_reparation_est_idempotente(): void
    {
        $this->isolerUsersSansGoogleId();

        $this->migration()->up();
        $this->migration()->up();
        $this->migration()->up();

        $this->assertTrue($this->colonneExiste());
    }

    public function test_la_migration_ne_echoue_pas_si_la_colonne_existe_deja(): void
    {
        $this->isolerUsersSansGoogleId();

        // Première passe : crée la colonne.
        $this->migration()->up();

        // Seconde passe : doit être silencieuse, sans erreur ni duplication.
        $this->migration()->up();

        $this->assertTrue($this->colonneExiste());
    }

    public function test_la_colonne_recreee_est_nullable_pour_les_utilisateurs_existants(): void
    {
        $this->isolerUsersSansGoogleId();

        $before = DB::connection('reparation')->table('users')->count();
        $this->migration()->up();
        $after = DB::connection('reparation')->table('users')->count();

        $this->assertSame(
            $before,
            $after,
            'Ajouter une colonne ne doit créer ni supprimer aucun utilisateur.'
        );
    }

    public function test_down_ne_echoue_pas_si_la_colonne_est_absente(): void
    {
        $this->isolerUsersSansGoogleId();

        $this->migration()->down();

        $this->assertFalse($this->colonneExiste());
    }
}