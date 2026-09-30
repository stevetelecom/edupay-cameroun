<?php

namespace App\Jobs;

use App\Mail\AccuseReclamationMail;
use App\Mail\NouvelleReclamationMail;
use App\Models\Admin;
use App\Models\NotificationAdmin;
use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie l'ouverture d'un ticket de réclamation.
 *
 * Trois canaux, chacun isolé dans son propre try/catch : un SMTP en panne ne
 * doit ni faire échouer l'enregistrement de la réclamation (déjà commité avant
 * le dispatch), ni priver le payeur de son accusé de réception parce que
 * l'alerte au support a échoué.
 *
 * Le job est dans la file (ShouldQueue) et `afterCommit`, donc aucun email ne
 * part sur une transaction qui finirait par un rollback.
 */
class NotifierNouvelleReclamation implements ShouldQueue
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
        $adresse     = config('mail.contact_address', config('mail.from.address'));

        $this->notifierAdmins($reclamation);

        // 1. Alerte au support, en reply-to pour permettre un retour direct.
        try {
            Mail::to($adresse)->send(new NouvelleReclamationMail($reclamation));
            Log::channel('admin')->info('Email nouvelle reclamation envoyé', [
                'reclamation_id' => $reclamation->id,
                'ticket'         => $reclamation->numero_ticket,
                'to'             => $adresse,
            ]);
        } catch (\Throwable $e) {
            Log::channel('admin')->error('Erreur envoi email nouvelle reclamation', [
                'reclamation_id' => $reclamation->id,
                'error'          => $e->getMessage(),
            ]);
        }

        // 2. Accusé de réception au payeur. Respecte son refus des e-mails,
        //    mais pas son `notif_email` : c'est la preuve d'avoir été entendu.
        if ($payeur?->email) {
            try {
                Mail::to($payeur->email)->send(new AccuseReclamationMail($reclamation));
                Log::channel('admin')->info('Email accuse reclamation envoyé', [
                    'reclamation_id' => $reclamation->id,
                    'user_id'        => $payeur->id,
                ]);
            } catch (\Throwable $e) {
                Log::channel('admin')->error('Erreur envoi email accuse reclamation', [
                    'reclamation_id' => $reclamation->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Une ligne par admin actif : le compteur de la cloche est par destinataire,
     * donc un admin qui lit le ticket n'éteint pas la cloche d'un collègue.
     */
    private function notifierAdmins(Reclamation $reclamation): void
    {
        try {
            $admins = Admin::query()->get();

            foreach ($admins as $admin) {
                NotificationAdmin::create([
                    'admin_id'       => $admin->id,
                    'type'           => 'reclamation',
                    'titre'          => 'Nouvelle réclamation '.$reclamation->numero_ticket,
                    'message'        => $reclamation->sujet,
                    'reclamation_id' => $reclamation->id,
                    'url'            => route('admin.reclamations.index'),
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('admin')->error('Erreur creation notification admin', [
                'reclamation_id' => $reclamation->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
