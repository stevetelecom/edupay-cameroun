<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les reversements etaient muets : un echec ne laissait AUCUNE trace
 * consultable (commissions.statut neemyait que 'calculee' / 'prelevee'), le
 * job avalait l'echec sans lever d'exception (donc failed() jamais appele) et
 * aucune commande ne pouvait rejouer un reversement bloque. Un etablissement
 * pouvait ainsi ne jamais etre paye, indefiniment, sans que personne ne le sache.
 *
 * Nouveaux etats :
 *   en_cours   l'appel AangaraaPay est parti, on ne sait pas encore
 *   a_verifier reponse perdue (timeout) : l'argent est peut-etre sorti, on ne
 *              RENVOIE JAMAIS automatiquement, verification humaine requise
 *   echec      echec definitif (3 tentatives) : reversement manuel a faire
 *
 * 'calculee' reste le seul etat eligible a un rejeu automatique.
 */
return new class extends Migration
{
    private const ETATS = ['calculee', 'en_cours', 'prelevee', 'a_verifier', 'echec'];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commissions MODIFY statut ENUM('calculee','en_cours','prelevee','a_verifier','echec') NOT NULL DEFAULT 'calculee'");
        } else {
            Schema::table('commissions', function (Blueprint $table) {
                $table->enum('statut', self::ETATS)->default('calculee')->change();
            });
        }

        // Trace des tentatives : sans elle, un reversement bloque ne laissait
        // aucune trace consultable (ni quand, ni combien de fois, ni pourquoi).
        Schema::table('commissions', function (Blueprint $table) {
            $table->timestamp('reversement_tente_le')->nullable()->after('reversed_at');
            $table->string('reversement_erreur', 500)->nullable()->after('reversement_tente_le');
        });
    }

    public function down(): void
    {
        // Un etat intermediate ne peut pas etre retrograde tel quel : on ne
        // conserve que les reversements reellement aboutis, les autres
        // repartent en 'calculee' pour etre rejoues.
        DB::table('commissions')->whereIn('statut', ['en_cours', 'a_verifier', 'echec'])
            ->update(['statut' => 'calculee']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commissions MODIFY statut ENUM('calculee','prelevee') NOT NULL DEFAULT 'calculee'");
        } else {
            Schema::table('commissions', function (Blueprint $table) {
                $table->enum('statut', ['calculee', 'prelevee'])->default('calculee')->change();
            });
        }

        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn(['reversement_tente_le', 'reversement_erreur']);
        });
    }
};
