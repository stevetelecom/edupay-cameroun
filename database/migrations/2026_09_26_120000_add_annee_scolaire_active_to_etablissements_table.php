<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annee scolaire active par etablissement (audit A).
 * NULL => AnneeScolaire::active() calcule l'annee en cours (rentree septembre).
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('etablissements', 'annee_scolaire_active')) {
            Schema::table('etablissements', function (Blueprint $table) {
                $table->string('annee_scolaire_active', 9)->nullable()->after('code_etablissement');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('etablissements', 'annee_scolaire_active')) {
            Schema::table('etablissements', function (Blueprint $table) {
                $table->dropColumn('annee_scolaire_active');
            });
        }
    }
};
