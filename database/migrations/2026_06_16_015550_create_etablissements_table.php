<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->string('code_etablissement')->unique();
            $table->string('nom');
            $table->enum('type', ['maternelle','primaire','college','lycee_general','lycee_technique','universite','institut_prive','groupe_scolaire']);
            $table->enum('statut_juridique', ['public','prive_laic','prive_catholique','prive_protestant','prive_islamique']);
            $table->string('numero_agrement')->nullable();
            $table->enum('nb_eleves', ['moins_100','100_300','300_500','500_1000','plus_1000'])->nullable();
            $table->enum('region', ['centre','littoral','ouest','nord','adamaoua','est','sud','sud_ouest','nord_ouest','extreme_nord']);
            $table->string('ville');
            $table->string('quartier')->nullable();
            $table->string('boite_postale')->nullable();
            $table->string('telephone');
            $table->string('email');
            $table->string('site_web')->nullable();
            $table->enum('mobile_money_principal', ['mtn','orange','les_deux'])->nullable();
            $table->string('document_agrement')->nullable();
            $table->text('description')->nullable();
            $table->enum('statut', ['en_attente','actif','suspendu'])->default('en_attente');
            $table->decimal('taux_commission', 5, 4)->default(0.0050);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('etablissements'); }
};
