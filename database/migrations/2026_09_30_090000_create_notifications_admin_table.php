<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * File de notifications du Super Admin, en miroir exact de
     * `notifications_payeur` (voir 2026_07_11_140000).
     *
     * Une ligne par administrateur : le compteur de la cloche compte les lignes
     * non lues de l'admin connecté, donc deux admins ne se valident pas la
     * notification l'un de l'autre.
     *
     * `reclamation_id` est facultatif et cascade en null : une notification
     * d'un autre type (paiement, reversement) n'a pas de ticket, et supprimer
     * une réclamation ne doit pas laisser un pointeur invalide dans la file.
     */
    public function up(): void
    {
        if (Schema::hasTable('notifications_admin')) {
            return;
        }

        Schema::create('notifications_admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('type')->default('info'); // reclamation, info, warning, error, success
            $table->string('titre');
            $table->text('message');
            $table->foreignId('reclamation_id')->nullable()->constrained('reclamations')->nullOnDelete();
            $table->string('url')->nullable();
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'lu_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_admin');
    }
};
