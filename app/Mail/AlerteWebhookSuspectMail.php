<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlerteWebhookSuspectMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reference,
        public ?string $statutAnnonce,
        public string $statutReel,
        public string $ip,
        public array $payloadComplet,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚨 ALERTE SÉCURITÉ — Webhook AangaraaPay suspect',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alerte-webhook-suspect');
    }

    public function attachments(): array
    {
        return [];
    }
}
