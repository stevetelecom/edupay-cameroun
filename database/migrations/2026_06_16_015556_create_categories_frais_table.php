<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categories_frais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('montant_total', 12, 2);
            $table->boolean('fractionnable')->default(true);
            $table->integer('nb_tranches_max')->default(2);
            $table->boolean('actif')->default(true);
            $table->string('annee_scolaire')->default('2025-2026');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('categories_frais'); }
};
