<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_apprenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->foreignId('categorie_frais_id')->constrained('categories_frais')->cascadeOnDelete();
            $table->decimal('montant_total', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->enum('statut', ['regle', 'partiel', 'impaye'])->default('impaye');
            $table->string('annee_scolaire')->default('2025-2026');
            $table->timestamps();
            $table->unique(['apprenant_id', 'categorie_frais_id', 'annee_scolaire'], 'frais_apprenant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_apprenant');
    }
};
