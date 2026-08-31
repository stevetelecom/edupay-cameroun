<?php

namespace App\Mail;

use App\Models\Apprenant;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RelanceImpayeMail extends Mailable
{
    public function __construct(
        public Apprenant $apprenant,
        public string $categorieFraisNom,
        public float $resteAPayer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'EduPay Cameroun — Solde impayé à régulariser',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.relance-impaye',
        );
    }
}
