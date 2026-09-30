<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prépare la suppression RÉELLE d'un compte payeur par le back-office super admin.
 *
 * Le CDC (EDUPAY-CM-2026-001 §3.2) exige une « traçabilité complète et
 * infalsifiable de toutes les transactions financières de la plateforme », et le
 * §8.3 impose des logs « conservés 12 mois min ». Supprimer les lignes de
 * `paiements`, `notifications` et `remboursements` avec le compte violerait donc
 * les deux exigences : le relevé financier d'un payeur doit survivre à la
 * disparition de la personne.
 *
 * Ces trois colonnes référençaient `users` en RESTRICT + NOT NULL, ce qui rendait
 * impossible tout `DELETE` SQL du compte dès le premier paiement : le back-office
 * ne pouvait que poser un `deleted_at` (soft delete), c'est-à-dire ne rien
 * supprimer du tout.
 *
 * On les rend donc NULLables et on bascule les clés étrangères en
 * `ON DELETE SET NULL` : le `DELETE FROM users` devient réellement possible, et
 * PostgreSQL/MySQL détachent automatiquement les lignes financières qui
 * conservent leur montant, leur référence, leur opérateur et leur date. La
 * traçabilité est préservée, la personne disparaît.
 */
return new class extends Migration {
    /**
     * Colonnes rattachees a `users` qui doivent devenir NULLables.
     *
     * `dropForeign()` est appele avec le tableau de colonnes et non avec le nom
     * de la contrainte : sur SQLite, un `dropForeign('nom')` leve « This database
     * driver does not support dropping foreign keys by name » (seul l'alter de
     * table gere le cas, via reconstruction). Sur MySQL/PostgreSQL, la forme
     * tableau recompose le nom conventionnel `{table}_{colonne}_foreign`, qui est
     * exactement celui pose par les migrations d'origine.
     */
    private array $references = [
        ['paiements',      'user_id'],
        ['notifications',  'user_id'],
        ['remboursements', 'initie_par'],
    ];

    public function up(): void
    {
        foreach ($this->references as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->foreignId($column)->nullable()->change();
            });

            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->foreign($column)
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->references as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            // Toute ligne détachée par la montée de version rendrait la
            // contrainte NOT NULL inapplicable : la rollback est volontairement
            // refusée plutôt que destructive.
            $detachees = DB::table($table)->whereNull($column)->count();
            if ($detachees > 0) {
                throw new RuntimeException(
                    "Rollback impossible : {$table}.{$column} contient {$detachees} ligne(s) "
                    . 'détachée(s) par une suppression de compte. Rétablissez un compte associé '
                    . 'à ces lignes avant de restaurer la contrainte NOT NULL.'
                );
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });

            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->foreignId($column)->nullable(false)->change();
            });

            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->foreign($column)
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();
            });
        }
    }
};
