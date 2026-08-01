<?php

namespace App\Jobs;

use App\Mail\PaiementEchoueMail;
use App\Models\Paiement;
use App\Services\SmsService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationEchecPaiement
{
    use Dispatchable;

    public function __construct(public Paiement $paiement, public ?string $raison = null)
    {
    }

    public function handle(SmsService $smsService): void
    {
        $this->paiement->load(['user', 'apprenant', 'fraisApprenant.categorieFrais']);

        $user      = $this->paiement->user;
        $apprenant = $this->paiement->apprenant;
        $montant   = number_format($this->paiement->montant, 0, ',', ' ');
        $reference = $this->paiement->reference;

        if ($user->email && $user->notif_email) {
            try {
                Mail::to($user->email)->send(new PaiementEchoueMail($this->paiement, $this->raison));
                Log::channel('admin')->info('Email echec paiement envoye', [
                    'paiement_id' => $this->paiement->id,
                    'user_id'     => $user->id,
                ]);
            } catch (\Exception $e) {
                Log::channel('admin')->error('Erreur envoi email echec paiement', [
                    'paiement_id' => $this->paiement->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        if ($user->telephone && $user->notif_sms) {
            $message = sprintf(
                "EduPay Cameroun : Paiement non abouti.\n%s %s\nMontant : %s FCFA\nReference : %s\n%sReessayez depuis l'application.",
                $apprenant->nom,
                $apprenant->prenom,
                $montant,
                $reference,
                $this->raison ? "Motif : {$this->raison}\n" : ''
            );

            $ok = $smsService->envoyer($user->telephone, $message);
            if ($ok) {
                Log::channel('admin')->info('SMS echec paiement envoye', [
                    'paiement_id' => $this->paiement->id,
                    'user_id'     => $user->id,
                ]);
            } else {
                Log::channel('admin')->error('Echec SMS notification echec paiement', [
                    'paiement_id' => $this->paiement->id,
                ]);
            }
        }
    }
}
