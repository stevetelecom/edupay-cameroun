<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('echeanciers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_frais_id')->constrained('categories_frais')->cascadeOnDelete();
            $table->integer('numero_tranche');
            $table->decimal('montant', 12, 2);
            $table->date('date_echeance');
            $table->string('libelle')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('echeanciers'); }
};
