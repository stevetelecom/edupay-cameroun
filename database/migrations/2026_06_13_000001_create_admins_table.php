<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('prenom', 80);
            $table->string('nom', 80);
            $table->string('email', 150)->unique();
            $table->string('telephone', 20)->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('derniere_connexion')->nullable();
            $table->string('derniere_connexion_ip', 45)->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};