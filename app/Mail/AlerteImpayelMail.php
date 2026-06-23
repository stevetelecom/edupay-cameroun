<?php

namespace App\Mail;

use App\Models\Apprenant;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AlerteImpayelMail extends Mailable
{
    public function __construct(
        public Apprenant $apprenant,
        public string $categorieFraisNom,
        public float $montantDu,
        public string $dateEcheance = ''
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerte de paiement impayé - EduPay Cameroun',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerte-impaye',
            with: [
                'apprenant' => $this->apprenant,
                'categorieFraisNom' => $this->categorieFraisNom,
                'montantDu' => $this->montantDu,
                'dateEcheance' => $this->dateEcheance,
            ],
        );
    }
}
