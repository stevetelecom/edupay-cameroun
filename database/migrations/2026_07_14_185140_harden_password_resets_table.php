<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            // Le code sera désormais hashé (bcrypt ~60 caractères) au lieu d'être en clair
            $table->string('code', 255)->change();
            // Compteur de tentatives de vérification échouées (anti brute-force)
            $table->unsignedTinyInteger('tentatives')->default(0)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->string('code', 6)->change();
            $table->dropColumn('tentatives');
        });
    }
};
