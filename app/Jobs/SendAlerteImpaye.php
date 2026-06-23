<?php

namespace App\Jobs;

use App\Mail\AlerteImpayelMail;
use App\Models\Apprenant;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAlerteImpaye implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Apprenant $apprenant,
        public string $categorieFraisNom,
        public float $montantDu,
        public string $dateEcheance = ''
    ) {
    }

    public function handle(SmsService $smsService): void
    {
        $this->apprenant->load('parents');

        $montantFormate = number_format($this->montantDu, 0, ',', ' ');

        foreach ($this->apprenant->parents as $parent) {
            // 1️⃣ Envoyer l'EMAIL
            if ($parent->email && $parent->notif_email) {
                try {
                    Mail::to($parent->email)->send(new AlerteImpayelMail(
                        $this->apprenant,
                        $this->categorieFraisNom,
                        $this->montantDu,
                        $this->dateEcheance
                    ));
                    Log::channel('admin')->info('Email alerte impayé envoyé', [
                        'apprenant_id' => $this->apprenant->id,
                        'parent_id' => $parent->id,
                    ]);
                } catch (\Exception $e) {
                    Log::channel('admin')->error('Erreur envoi email alerte impayé', [
                        'apprenant_id' => $this->apprenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 2️⃣ Envoyer le SMS
            if ($parent->telephone && $parent->notif_sms) {
                $message = sprintf(
                    "EduPay Cameroun : Alerte impaye !\n%s %s\n%s\nMontant du : %s FCFA\nPayez rapidement via l'app EduPay.",
                    $this->apprenant->nom,
                    $this->apprenant->prenom,
                    $this->categorieFraisNom,
                    $montantFormate
                );

                $ok = $smsService->envoyer($parent->telephone, $message);
                if ($ok) {
                    Log::channel('admin')->info('SMS alerte impayé envoyé', [
                        'apprenant_id' => $this->apprenant->id,
                        'parent_id' => $parent->id,
                    ]);
                } else {
                    Log::channel('admin')->error('Échec SMS alerte impayé', [
                        'apprenant_id' => $this->apprenant->id,
                    ]);
                }
            }
        }
    }
}
