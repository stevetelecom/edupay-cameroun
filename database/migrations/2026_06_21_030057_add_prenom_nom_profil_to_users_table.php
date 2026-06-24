<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('prenom', 100)->nullable()->after('id');
            $table->string('nom', 100)->nullable()->after('prenom');
            $table->enum('profil', ['parent', 'eleve', 'etudiant'])->default('parent')->after('quartier');
            $table->boolean('notif_sms')->default(true)->after('profil');
            $table->boolean('notif_email')->default(true)->after('notif_sms');
            $table->string('email')->nullable()->change();
        });

        DB::statement("UPDATE users SET prenom = SUBSTRING_INDEX(name, ' ', 1), nom = SUBSTRING(name, LOCATE(' ', name) + 1)");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'nom', 'profil', 'notif_sms', 'notif_email']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
