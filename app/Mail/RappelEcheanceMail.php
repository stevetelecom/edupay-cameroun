<?php

namespace App\Mail;

use App\Models\Apprenant;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RappelEcheanceMail extends Mailable
{
    public function __construct(
        public Apprenant $apprenant,
        public string $categorieFraisNom,
        public float $resteAPayer,
        public string $dateEcheance
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'EduPay — Rappel échéance dans 5 jours',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rappel-echeance',
        );
    }
}
