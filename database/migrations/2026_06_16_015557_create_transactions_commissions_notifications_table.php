<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained('paiements')->cascadeOnDelete();
            $table->string('reference_operateur')->nullable();
            $table->string('operateur');
            $table->decimal('montant', 12, 2);
            $table->enum('statut', ['pending','success','failed'])->default('pending');
            $table->json('payload_request')->nullable();
            $table->json('payload_response')->nullable();
            $table->timestamp('callback_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained('paiements')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements');
            $table->decimal('montant_transaction', 12, 2);
            $table->decimal('taux', 5, 4);
            $table->decimal('montant_commission', 12, 2);
            $table->enum('statut', ['calculee','prelevee'])->default('calculee');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('paiement_id')->nullable()->constrained('paiements')->nullOnDelete();
            $table->enum('canal', ['sms','email']);
            $table->enum('type', ['paiement_confirme','recu_pdf','rappel_echeance','relance_impaye','bienvenue','inscription_ecole']);
            $table->string('destinataire');
            $table->text('contenu');
            $table->enum('statut', ['envoye','echec','en_attente'])->default('en_attente');
            $table->timestamp('envoye_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('transactions');
    }
};
