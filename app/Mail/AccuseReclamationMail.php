<?php

namespace App\Mail;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Accusé de réception envoyé au payeur.
 *
 * Contient le numéro de ticket : c'est la seule preuve que le payeur a une
 * trace écrite d'avoir été entendu. Indispensable ici, car le compte de test
 * qui a déclenché ce besoin n'avait pas d'email vérifié.
 */
class AccuseReclamationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reclamation $reclamation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Réclamation {$this->reclamation->numero_ticket} bien reçue — EduPay Cameroun",
        );
    }

    public function content(): Content
    {
        $this->reclamation->loadMissing(['user']);

        return new Content(
            view: 'emails.accuse-reclamation',
            with: ['reclamation' => $this->reclamation],
        );
    }
}
