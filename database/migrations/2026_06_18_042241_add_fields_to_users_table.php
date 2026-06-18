<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telephone')->unique()->nullable()->after('email');
            $table->string('ville')->nullable()->after('telephone');
            $table->string('quartier')->nullable()->after('ville');
            $table->foreignId('etablissement_id')->nullable()->after('quartier')
                  ->constrained('etablissements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['etablissement_id']);
            $table->dropColumn(['telephone', 'ville', 'quartier', 'etablissement_id']);
        });
    }
};
