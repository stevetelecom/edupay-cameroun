<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('acteur_type', 100)->default('anonyme');
            $table->unsignedBigInteger('acteur_id')->nullable();
            $table->string('action', 100);
            $table->text('detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->enum('niveau', ['INFO', 'WARNING', 'CRITICAL'])->default('INFO');
            $table->json('donnees_avant')->nullable();
            $table->json('donnees_apres')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Index pour les recherches rapides
            $table->index(['acteur_type', 'acteur_id']);
            $table->index('action');
            $table->index('niveau');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
