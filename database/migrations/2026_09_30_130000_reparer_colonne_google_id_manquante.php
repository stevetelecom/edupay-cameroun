<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Répare l'état « fantôme » constaté en production le 30/09/2026.
 *
 * Symptôme : la connexion Google renvoyait une erreur 500 et le journal
 * indiquait `SQLSTATE[42S22]: Unknown column 'google_id' in 'WHERE'`, alors que
 * `php artisan migrate:status` annonçait « 0 migration à appliquer ».
 *
 * Cause : la migration `2026_08_31_200338_add_google_id_to_users_table` est
 * enregistrée dans la table `migrations` (batch 11) mais la colonne n'a jamais
 * été créée — schéma et table `migrations` divergeaient. Laravel saute donc
 * définitivement la migration, et aucun redéploiement n'aurait jamais réparé le
 * 500.
 *
 * Cette migration est idempotente : elle n'ajoute la colonne que si elle est
 * réellement absente. Sur une base saine elle ne fait rien. Elle laisse la
 * ligne existante de la table `migrations` en place, puisqu'une fois la colonne
 * créée cette ligne redevient enfin exacte.
 *
 * Ajouter la colonne est sans risque : `google_id` est nullable, donc toutes
 * les lignes existantes restent valides, et l'index unique n'exclut pas les
 * NULL multiples en MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'google_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('password');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'google_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};