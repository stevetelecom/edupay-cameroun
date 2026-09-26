<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le statut 'annule' rend l'annulation REELLE (audit C).
 * Avant : 'annule_manuellement' seul etait positionne, le statut restait
 * 'en_attente' et traiterPaiementValide ignorait ce drapeau -> le client
 * affichait « annule » alors que le paiement pouvait encore etre encaisse.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE paiements MODIFY statut ENUM('valide','en_attente','echoue','rembourse','annule') NOT NULL DEFAULT 'en_attente'");
        } else {
            // SQLite / autres : reconstruction de la colonne via l'API schema
            Schema::table('paiements', function (Blueprint $table) {
                $table->enum('statut', ['valide', 'en_attente', 'echoue', 'rembourse', 'annule'])
                      ->default('en_attente')
                      ->change();
            });
        }

        // Reprise des annulations de l'ancien systeme : le drapeau etait pose
        // sans changer le statut, donc ces lignes doivent devenir terminales
        // elles aussi (sinon elles resteraient "en_attente" a l'infini).
        DB::table('paiements')
            ->where('statut', 'en_attente')
            ->where('annule_manuellement', true)
            ->update(['statut' => 'annule']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Les paiements annules sont remis en 'echoue' (etat terminal existant)
            DB::table('paiements')->where('statut', 'annule')->update(['statut' => 'echoue']);
            DB::statement("ALTER TABLE paiements MODIFY statut ENUM('valide','en_attente','echoue','rembourse') NOT NULL DEFAULT 'en_attente'");
            return;
        }

        DB::table('paiements')->where('statut', 'annule')->update(['statut' => 'echoue']);

        Schema::table('paiements', function (Blueprint $table) {
            $table->enum('statut', ['valide', 'en_attente', 'echoue', 'rembourse'])
                  ->default('en_attente')
                  ->change();
        });
    }
};
