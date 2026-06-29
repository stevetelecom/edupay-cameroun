<?php

namespace App\Mail;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Admin2FAMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Admin $admin,
        public string $otpCode,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[EduPay] Code de vérification Super Admin — Accès sécurisé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-2fa',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
