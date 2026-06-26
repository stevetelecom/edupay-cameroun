<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $contact;

    public function __construct(array $contact)
    {
        $this->contact = $contact;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau message de contact EduPay Cameroun',
            replyTo: $this->contact['email'] ? [$this->contact['email'] => $this->contact['name']] : null,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
            with: ['contact' => $this->contact],
        );
    }
}
