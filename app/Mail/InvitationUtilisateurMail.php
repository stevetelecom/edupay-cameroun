<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationUtilisateurMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $utilisateur,
        public string $motDePasseTemporaire,
        public string $roleLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur EduPay — votre accès Back-office',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-utilisateur',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
