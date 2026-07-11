<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('suspendu')->default(false)->after('profil');
            $table->string('suspendu_raison', 500)->nullable()->after('suspendu');
            $table->timestamp('suspendu_at')->nullable()->after('suspendu_raison');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspendu', 'suspendu_raison', 'suspendu_at']);
            $table->dropSoftDeletes();
        });
    }
};
