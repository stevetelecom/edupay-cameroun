<?php

namespace App\Mail;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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
        // L'expedition doit suivre MAIL_2FA_ADDRESS / MAIL_2FA_NAME, pas
        // MAIL_FROM_ADDRESS.
        //
        // Ces deux cles sont declarees dans config/mail.php depuis le commit
        // 4df1624 mais n'etaient lues par PERSONNE : sans cet Envelope, le
        // code partait de MAIL_FROM_ADDRESS, c'est-a-dire noreply@, une
        // adresse automatique. Gmail classe ce profil en spam et, apres
        // quelques envois rapproches a la meme adresse, bloque l'expediteur
        // SILENCIEUSEMENT : le SMTP continue d'accepter, le message ne
        // arrive jamais. Constat en production le 01/10/2026 : sept envois
        // acceptes entre 02:37 et 03:17 UTC, aucun recu, aucun spam.
        //
        // On retombe sur mail.from si les cles 2FA sont absentes, pour ne
        // pas casser une installation qui n'a pas defini MAIL_2FA_ADDRESS.
        return new Envelope(
            subject: '[EduPay] Code de vérification Super Admin — Accès sécurisé',
            from: new Address(
                config('mail.mailers.smtp.2fa_address') ?: config('mail.from.address'),
                config('mail.mailers.smtp.2fa_name') ?: config('mail.from.name'),
            ),
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
