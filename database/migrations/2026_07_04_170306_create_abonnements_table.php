<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->enum('plan', ['basique', 'standard', 'premium'])->default('basique');
            $table->integer('montant_mensuel'); // 5000 / 10000 / 20000
            $table->date('date_debut');
            $table->date('date_fin');
            $table->date('grace_period_fin'); // date_fin + 7 jours
            $table->enum('statut', ['actif', 'grace_period', 'expire', 'suspendu'])->default('actif');
            $table->string('reference_paiement')->nullable(); // ref du paiement MoMo reçu
            $table->text('notes')->nullable();
            $table->foreignId('active_par')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('active_at')->nullable();
            $table->timestamps();
        });

        // Ajouter plan_actuel sur etablissements
        Schema::table('etablissements', function (Blueprint $table) {
            $table->enum('plan_abonnement', ['aucun', 'basique', 'standard', 'premium'])
                  ->default('aucun')->after('statut');
            $table->date('abonnement_expire_le')->nullable()->after('plan_abonnement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
        Schema::table('etablissements', function (Blueprint $table) {
            $table->dropColumn(['plan_abonnement', 'abonnement_expire_le']);
        });
    }
};
