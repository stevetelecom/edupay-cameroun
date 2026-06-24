<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('apprenant_id')->constrained('apprenants');
            $table->foreignId('frais_apprenant_id')->constrained('frais_apprenant');
            $table->foreignId('echeancier_id')->nullable()->constrained('echeanciers');
            $table->decimal('montant', 12, 2);
            $table->enum('mode_paiement', ['mtn_momo','orange_money','carte']);
            $table->enum('type_paiement', ['integral','tranche'])->default('integral');
            $table->integer('numero_tranche')->nullable();
            $table->enum('statut', ['valide','en_attente','echoue','rembourse'])->default('en_attente');
            $table->string('telephone_paiement')->nullable();
            $table->timestamp('date_paiement')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('paiements'); }
};
