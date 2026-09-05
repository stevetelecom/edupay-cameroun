<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            // Securite (M-01 audit) : l'ancien systeme utilisait l'ID auto-incremente
            // comme "token" dans le lien de reinitialisation — previsible et enumerable
            // (?token=1, ?token=2...). On ajoute un vrai token aleatoire 64 hex, genere
            // uniquement APRES verification du code OTP, et utilise a la place de l'ID
            // dans l'URL renvoyee a l'utilisateur.
            $table->string('reset_token', 64)->nullable()->unique()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropColumn('reset_token');
        });
    }
};
