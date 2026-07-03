<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ajouter frais_service sur paiements (montant visible par le payeur)
        Schema::table('paiements', function (Blueprint $table) {
            $table->integer('frais_service')->default(0)->after('montant')
                  ->comment('Frais visibles par le payeur (EduPay + AangaraaPay fusionnés)');
            $table->integer('montant_total_paye')->default(0)->after('frais_service')
                  ->comment('montant + frais_service = ce que le payeur paie réellement');
            $table->integer('frais_aangaraa')->default(0)->after('montant_total_paye')
                  ->comment('Part AangaraaPay dans les frais (backend uniquement)');
            $table->integer('marge_edupay')->default(0)->after('frais_aangaraa')
                  ->comment('Marge EduPay sur cette transaction');
        });

        // Ajouter numéro MoMo reversement sur établissements
        Schema::table('etablissements', function (Blueprint $table) {
            $table->string('numero_momo_reversement')->nullable()->after('mobile_money_principal')
                  ->comment('Numéro MoMo pour recevoir les reversements EduPay');
            $table->enum('operateur_momo_reversement', ['mtn', 'orange'])->nullable()->after('numero_momo_reversement')
                  ->comment('Opérateur du compte MoMo de reversement');
        });

        // Ajouter montant_net sur commissions
        Schema::table('commissions', function (Blueprint $table) {
            $table->decimal('montant_net_etablissement', 12, 2)->default(0)->after('montant_commission')
                  ->comment('Montant reversé à l\'établissement après commission EduPay');
            $table->decimal('frais_aangaraa', 12, 2)->default(0)->after('montant_net_etablissement')
                  ->comment('Frais prélevés par AangaraaPay');
            $table->string('reference_reversement')->nullable()->after('frais_aangaraa')
                  ->comment('Référence du withdrawal AangaraaPay vers l\'établissement');
            $table->timestamp('reversed_at')->nullable()->after('reference_reversement')
                  ->comment('Date du reversement vers l\'établissement');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn(['frais_service', 'montant_total_paye', 'frais_aangaraa', 'marge_edupay']);
        });
        Schema::table('etablissements', function (Blueprint $table) {
            $table->dropColumn(['numero_momo_reversement', 'operateur_momo_reversement']);
        });
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn(['montant_net_etablissement', 'frais_aangaraa', 'reference_reversement', 'reversed_at']);
        });
    }
};
