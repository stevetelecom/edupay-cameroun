<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            // Empêche au niveau SQL qu'un même paiement génère 2 commissions
            // (protection ultime contre les race conditions webhook/polling)
            $table->unique('paiement_id');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropUnique(['paiement_id']);
        });
    }
};
