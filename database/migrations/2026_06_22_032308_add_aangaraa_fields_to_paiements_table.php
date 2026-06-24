<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('paiements', function (Blueprint $table) {
            // Token retourné par AangaraaPay pour vérifier le statut
            $table->string('pay_token')->nullable()->after('telephone_paiement');
            // ID interne AangaraaPay (payment_history_id)
            $table->string('aangaraa_transaction_id')->nullable()->after('pay_token');
            // Opérateur détecté (MTN_Cameroon / Orange_Cameroon)
            $table->string('operateur')->nullable()->after('aangaraa_transaction_id');
        });
    }

    public function down(): void {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn(['pay_token', 'aangaraa_transaction_id', 'operateur']);
        });
    }
};
