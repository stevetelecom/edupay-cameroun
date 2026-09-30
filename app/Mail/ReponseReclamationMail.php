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
 * Réponse de l'administrateur au payeur.
 *
 * Le `replyTo` est l'adresse de contact du support : le payeur qui répond à
 * cet email écrit au support, pas au compte personnel de l'admin qui a
 * traité le ticket.
 */
class ReponseReclamationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reclamation $reclamation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Réponse à votre réclamation {$this->reclamation->numero_ticket} — EduPay Cameroun",
            replyTo: [new Address(
                config('mail.contact_address', config('mail.from.address')),
                config('mail.from.name', 'EduPay Cameroun'),
            )],
        );
    }

    public function content(): Content
    {
        $this->reclamation->loadMissing(['user', 'paiement']);

        return new Content(
            view: 'emails.reponse-reclamation',
            with: ['reclamation' => $this->reclamation],
        );
    }
}
