<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('guard')->default('web'); // 'web' = User, 'admin' = Admin
            $table->string('code', 6); // Code de vérification à 6 chiffres
            $table->boolean('is_verified')->default(false); // Code vérifié ?
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Index pour les recherches rapides et nettoyage des tokens expirés
            $table->index(['email', 'guard', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
