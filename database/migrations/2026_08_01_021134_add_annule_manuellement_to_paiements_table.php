<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('paiements', function (Blueprint $table) {
            // Annulation payeur d'un paiement en_attente : NE change PAS le statut
            // reel (reste en_attente) pour ne jamais bloquer une confirmation
            // tardive et legitime de l'operateur. Sert uniquement a debloquer
            // un nouvel essai avant la fin du delai de blocage de 5 minutes.
            $table->boolean('annule_manuellement')->default(false)->after('statut');
        });
    }
    public function down(): void {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn('annule_manuellement');
        });
    }
};
