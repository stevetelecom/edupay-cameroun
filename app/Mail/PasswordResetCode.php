<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetCode extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $userName,
        public string $expiresIn = '15 minutes',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Code de réinitialisation de mot de passe - EduPay Cameroun',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-code',
            with: [
                'code' => $this->code,
                'userName' => $this->userName,
                'expiresIn' => $this->expiresIn,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
