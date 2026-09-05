<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite (utilisé par les tests PHPUnit) n'a pas de vrai type ENUM et
        // ne supporte pas ALTER TABLE ... MODIFY : les colonnes y sont déjà en
        // TEXT libre, donc aucune contrainte à assouplir — rien à faire ici.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('etablissements', function (Blueprint $table) {
            // 'type' : accepte les valeurs de l'ancien schéma (déjà en base) ET celles
            // proposées par le formulaire/validation (sinon UPDATE échoue -> 500).
            DB::statement("ALTER TABLE etablissements MODIFY type ENUM('maternelle','primaire','college','lycee_general','lycee_technique','universite','institut_prive','groupe_scolaire','secondaire','universitaire','formation')");
            // 'statut_juridique' et 'region' : passés en string car la vue envoie des
            // valeurs libres (texte / régions affichées) incompatibles avec les enums
            // initiaux -> ailleurs tout UPDATE avec une valeur hors liste produisait un 500.
            DB::statement("ALTER TABLE etablissements MODIFY statut_juridique VARCHAR(100) NULL");
            DB::statement("ALTER TABLE etablissements MODIFY region VARCHAR(100) NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('etablissements', function (Blueprint $table) {
            DB::statement("ALTER TABLE etablissements MODIFY type ENUM('maternelle','primaire','college','lycee_general','lycee_technique','universite','institut_prive','groupe_scolaire')");
            DB::statement("ALTER TABLE etablissements MODIFY statut_juridique ENUM('public','prive_laic','prive_catholique','prive_protestant','prive_islamique') NULL");
            DB::statement("ALTER TABLE etablissements MODIFY region ENUM('centre','littoral','ouest','nord','adamaoua','est','sud','sud_ouest','nord_ouest','extreme_nord') NULL");
        });
    }
};
