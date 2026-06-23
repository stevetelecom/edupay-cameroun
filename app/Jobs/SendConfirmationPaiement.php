<?php

namespace App\Jobs;

use App\Mail\ConfirmationPaiementMail;
use App\Models\Paiement;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendConfirmationPaiement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Paiement $paiement)
    {
    }

    public function handle(SmsService $smsService): void
    {
        $this->paiement->load(['user', 'apprenant', 'fraisApprenant.categorieFrais']);

        $user = $this->paiement->user;
        $apprenant = $this->paiement->apprenant;
        $montant = number_format($this->paiement->montant, 0, ',', ' ');
        $reference = $this->paiement->reference;

        // 1️⃣ Envoyer l'EMAIL
        if ($user->email && $user->notif_email) {
            try {
                Mail::to($user->email)->send(new ConfirmationPaiementMail($this->paiement));
                Log::channel('admin')->info('Email confirmation paiement envoyé', [
                    'paiement_id' => $this->paiement->id,
                    'user_id' => $user->id,
                ]);
            } catch (\Exception $e) {
                Log::channel('admin')->error('Erreur envoi email confirmation paiement', [
                    'paiement_id' => $this->paiement->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2️⃣ Envoyer le SMS
        if ($user->telephone && $user->notif_sms) {
            $message = sprintf(
                "EduPay Cameroun : Paiement confirme !\n%s %s\nMontant : %s FCFA\nReference : %s\nMerci d'avoir paye !",
                $apprenant->nom,
                $apprenant->prenom,
                $montant,
                $reference
            );

            $ok = $smsService->envoyer($user->telephone, $message);
            if ($ok) {
                Log::channel('admin')->info('SMS confirmation paiement envoyé', [
                    'paiement_id' => $this->paiement->id,
                    'user_id' => $user->id,
                ]);
            } else {
                Log::channel('admin')->error('Échec SMS confirmation paiement', [
                    'paiement_id' => $this->paiement->id,
                ]);
            }
        }
    }
}
