<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_apprenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained('apprenants')->cascadeOnDelete();
            $table->string('lien')->default('parent');
            $table->timestamps();
            $table->unique(['user_id', 'apprenant_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('user_apprenant'); }
};
