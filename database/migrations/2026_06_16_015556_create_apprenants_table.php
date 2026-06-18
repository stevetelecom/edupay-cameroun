<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('apprenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom');
            $table->string('prenom');
            $table->string('classe');
            $table->string('matricule')->nullable()->unique();
            $table->date('date_naissance')->nullable();
            $table->enum('sexe', ['M','F'])->nullable();
            $table->enum('statut_paiement', ['regle','partiel','impaye'])->default('impaye');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('apprenants'); }
};
