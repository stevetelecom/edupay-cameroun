<?php

namespace App\Mail;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte adressée à l'adresse de contact quand un payeur ouvre un ticket.
 *
 * Le `replyTo` est l'email du payeur : répondre à cette alerte dans la
 * messagerie du support écrit directement au parent, sans repasser par le
 * back-office. C'est ce qui rend l'échange admin <-> payeur possible par
 * email dans les deux sens.
 */
class NouvelleReclamationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reclamation $reclamation)
    {
    }

    public function envelope(): Envelope
    {
        $payeur = $this->reclamation->user;

        return new Envelope(
            subject: "[Ticket {$this->reclamation->numero_ticket}] Nouvelle réclamation — {$this->reclamation->sujet}",
            replyTo: $payeur?->email
                ? [new Address($payeur->email, trim(($payeur->prenom ?? '').' '.($payeur->nom ?? '')) ?: $payeur->email)]
                : null,
        );
    }

    public function content(): Content
    {
        $this->reclamation->loadMissing(['user', 'paiement']);

        return new Content(
            view: 'emails.nouvelle-reclamation',
            with: ['reclamation' => $this->reclamation],
        );
    }
}
