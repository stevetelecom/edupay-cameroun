<?php
namespace App\Mail;
use App\Models\Admin;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminResetPasswordMail extends Mailable
{
    public function __construct(public Admin $admin, public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'EduPay — Code de réinitialisation Super Admin');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-reset-password');
    }
}
