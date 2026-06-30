<?php
namespace App\Mail;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EtablissementSuspenduMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(
        public Etablissement $etablissement,
        public User $responsable,
        public ?string $raison = null,
    ) {}
    public function envelope(): Envelope
    {
        return new Envelope(subject: '⚠️ Votre établissement a été suspendu sur EduPay');
    }
    public function content(): Content
    {
        return new Content(view: 'emails.etablissement-suspendu');
    }
    public function attachments(): array { return []; }
}
