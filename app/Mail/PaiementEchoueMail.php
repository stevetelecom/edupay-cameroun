<?php

namespace App\Mail;

use App\Models\Paiement;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaiementEchoueMail extends Mailable
{
    public function __construct(public Paiement $paiement, public ?string $raison = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement non abouti - EduPay Cameroun',
        );
    }

    public function content(): Content
    {
        $this->paiement->load(['apprenant', 'fraisApprenant.categorieFrais', 'user']);

        return new Content(
            view: 'emails.paiement-echoue',
            with: [
                'paiement' => $this->paiement,
                'raison'   => $this->raison,
            ],
        );
    }
}
