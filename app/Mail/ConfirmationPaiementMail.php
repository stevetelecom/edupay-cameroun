<?php

namespace App\Mail;

use App\Models\Paiement;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmationPaiementMail extends Mailable
{
    public function __construct(public Paiement $paiement)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de paiement - EduPay Cameroun',
        );
    }

    public function content(): Content
    {
        $this->paiement->load(['apprenant', 'fraisApprenant.categorieFrais', 'user']);

        return new Content(
            view: 'emails.confirmation-paiement',
            with: [
                'paiement' => $this->paiement,
            ],
        );
    }
}
