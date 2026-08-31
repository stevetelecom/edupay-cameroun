<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParentOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otpCode,
        public string $type = 'otp',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->type === 'relance'
                ? '[EduPay] Votre code de connexion sécurisée'
                : '[EduPay] Votre code de vérification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.parent-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
