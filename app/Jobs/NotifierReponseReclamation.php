<?php

namespace App\Jobs;

use App\Mail\ReponseReclamationMail;
use App\Models\NotificationPayeur;
use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie le payeur de la réponse de l'administrateur.
 *
 * Deux canaux : l'email (avec reply-to sur l'adresse de contact, pour que la
 * conversation continue depuis la messagerie) et la notification in-app qui
 * fait apparaître la pastille sur la cloche de son tableau de bord.
 */
class NotifierReponseReclamation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Reclamation $reclamation)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $reclamation = $this->reclamation;
        $payeur      = $reclamation->user;

        if (! $payeur) {
            return;
        }

        $libelle = $reclamation->statutLibelle();

        $this->creerNotificationPayeur($reclamation, $libelle);

        if (! $payeur->email) {
            return;
        }

        try {
            Mail::to($payeur->email)->send(new ReponseReclamationMail($reclamation));
            Log::channel('admin')->info('Email reponse reclamation envoyé', [
                'reclamation_id' => $reclamation->id,
                'ticket'         => $reclamation->numero_ticket,
                'user_id'        => $payeur->id,
            ]);
        } catch (\Throwable $e) {
            Log::channel('admin')->error('Erreur envoi email reponse reclamation', [
                'reclamation_id' => $reclamation->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * La cloche du tableau de bord payeur compte les lignes non lues : cette
     * ligne est ce qui fait apparaître le badge après une réponse de l'admin.
     */
    private function creerNotificationPayeur(Reclamation $reclamation, string $libelle): void
    {
        try {
            NotificationPayeur::create([
                'user_id' => $reclamation->user_id,
                'titre'   => 'Réponse à votre réclamation '.$reclamation->numero_ticket,
                'message' => $libelle.': '.str($reclamation->reponse_admin)->limit(120)->toString(),
                // Une réponse est une bonne nouvelle, sauf un rejet explicite.
                'type'    => $reclamation->statut === 'rejete' ? 'warning' : 'success',
            ]);
        } catch (\Throwable $e) {
            Log::channel('admin')->error('Erreur creation notification payeur', [
                'reclamation_id' => $reclamation->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
