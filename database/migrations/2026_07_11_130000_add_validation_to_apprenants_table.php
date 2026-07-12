<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('apprenants', function (Blueprint $table) {
            $table->enum('source', ['etablissement', 'payeur'])->default('etablissement')->after('actif');
            $table->boolean('valide_par_etablissement')->default(true)->after('source');
        });
    }
    public function down(): void {
        Schema::table('apprenants', function (Blueprint $table) {
            $table->dropColumn(['source', 'valide_par_etablissement']);
        });
    }
};
