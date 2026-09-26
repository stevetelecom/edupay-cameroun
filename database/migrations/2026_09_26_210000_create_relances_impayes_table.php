<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit U — tracabilite des relances d'impayes.
 *
 * Avant, aucune trace des relances envoyees : rien n'empechait
 * `relancerSms` / `relancerApprenant` d'etre appeles en boucle et de
 * spammer les memes parents, et `derniere_relance` (contrat mobile,
 * endpoint impayes) valait toujours null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relances_impayes', function (Blueprint $table) {
            $table->id();

            // Ligne de frais relancee (le reste du > 0 est verifie avant envoi).
            $table->foreignId('frais_apprenant_id')
                ->constrained('frais_apprenant')
                ->cascadeOnDelete();

            $table->foreignId('apprenant_id')
                ->constrained('apprenants')
                ->cascadeOnDelete();

            $table->foreignId('etablissement_id')
                ->constrained('etablissements')
                ->cascadeOnDelete();

            // Parent reellement contacte.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('canal', 20)->default('email');   // email | sms
            $table->string('statut', 20)->default('envoye'); // envoye | echec | ignore
            $table->text('erreur')->nullable();

            $table->timestamps();

            // Anti-spam : dernier envoi pour un couple (ligne de frais, parent).
            $table->index(['frais_apprenant_id', 'user_id', 'created_at'], 'relances_anti_spam');
            $table->index(['etablissement_id', 'created_at'], 'relances_par_etablissement');
            $table->index(['apprenant_id', 'created_at'], 'relances_par_apprenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relances_impayes');
    }
};
