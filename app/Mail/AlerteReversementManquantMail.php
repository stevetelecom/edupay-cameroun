<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte « un établissement n'a pas été payé ».
 *
 * Tant que cette alerte n'existait pas, un reversement bloqué était totalement
 * invisible : l'argent restait sur le compte AangaraaPay, l'établissement ne
 * touchait rien, et personne n'en savait rien.
 */
class AlerteReversementManquantMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?int $commissionId,
        public ?string $etablissement,
        public ?string $numeroReversement,
        public ?int $montant,
        public ?int $paiementId,
        public string $raison,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ALERTE - Reversement etablissement non effectue',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alerte-reversement-manquant');
    }

    public function attachments(): array
    {
        return [];
    }
}
