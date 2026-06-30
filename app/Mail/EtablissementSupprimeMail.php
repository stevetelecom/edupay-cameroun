<?php
namespace App\Mail;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EtablissementSupprimeMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(
        public string $nomEtablissement,
        public User $responsable,
    ) {}
    public function envelope(): Envelope
    {
        return new Envelope(subject: '❌ Votre établissement a été retiré de EduPay');
    }
    public function content(): Content
    {
        return new Content(view: 'emails.etablissement-supprime');
    }
    public function attachments(): array { return []; }
}
