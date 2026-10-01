<?php

namespace Tests\Feature;

use App\Mail\Admin2FAMail;
use App\Models\Admin;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Non-regression sur l'expediteur des codes 2FA Super Admin.
 *
 * Incident production du 01/10/2026 : MAIL_2FA_ADDRESS et MAIL_2FA_NAME
 * etaient declares dans config/mail.php depuis le commit 4df1624, mais
 * aucun code ne les lisait. Les codes partaient donc de MAIL_FROM_ADDRESS
 * (noreply@), profil qu'un Gmail bloque silencieusement apres quelques
 * envois rapproches sur la meme adresse : SMTP accepte, le message n'arrive
 * jamais. Symptome : sept envois acceptes dans laravel.log, zero recu, zero
 * spam.
 *
 * Ce test verrouille le contrat : le From suit MAIL_2FA_*, avec repli sur
 * mail.from pour ne pas casser une installation qui ne les definit pas.
 */
class Admin2FAMailExpediteurTest extends TestCase
{
    private function mail(): Admin2FAMail
    {
        $admin = new Admin([
            'prenom' => 'Olivier',
            'nom'    => 'MEKONTSO',
        ]);

        return new Admin2FAMail($admin, '123456');
    }

    public function test_from_suit_la_configuration_2fa(): void
    {
        config([
            'mail.mailers.smtp.2fa_address' => 'contact@mekontso.gsi2026.com',
            'mail.mailers.smtp.2fa_name'    => 'EduPay Cameroun — Sécurité',
            'mail.from.address'              => 'noreply@mekontso.gsi2026.com',
            'mail.from.name'                 => 'EduPay',
        ]);

        $envelope = $this->mail()->envelope();

        $this->assertSame('contact@mekontso.gsi2026.com', $envelope->from->address);
        $this->assertSame('EduPay Cameroun — Sécurité', $envelope->from->name);
    }

    public function test_repli_sur_mail_from_si_les_cles_2fa_sont_absentes(): void
    {
        config([
            'mail.mailers.smtp.2fa_address' => null,
            'mail.mailers.smtp.2fa_name'    => null,
            'mail.from.address'              => 'noreply@mekontso.gsi2026.com',
            'mail.from.name'                 => 'EduPay',
        ]);

        $envelope = $this->mail()->envelope();

        $this->assertSame('noreply@mekontso.gsi2026.com', $envelope->from->address);
        $this->assertSame('EduPay', $envelope->from->name);
    }

    public function test_repli_si_la_cle_adresse_est_vide_et_le_nom_present(): void
    {
        config([
            'mail.mailers.smtp.2fa_address' => '',
            'mail.mailers.smtp.2fa_name'    => 'Securite EduPay',
            'mail.from.address'              => 'noreply@mekontso.gsi2026.com',
            'mail.from.name'                 => 'EduPay',
        ]);

        $envelope = $this->mail()->envelope();

        $this->assertSame('noreply@mekontso.gsi2026.com', $envelope->from->address);
        $this->assertSame('Securite EduPay', $envelope->from->name);
    }

    public function test_le_from_est_efectivement_transmis_par_le_transport(): void
    {
        config([
            'mail.mailers.smtp.2fa_address' => 'contact@mekontso.gsi2026.com',
            'mail.mailers.smtp.2fa_name'    => 'EduPay Cameroun — Sécurité',
            'mail.from.address'              => 'noreply@mekontso.gsi2026.com',
        ]);

        Mail::fake();

        $admin = new Admin([
            'prenom' => 'Olivier',
            'nom'    => 'MEKONTSO',
        ]);

        Mail::to('moffosteve2@gmail.com')->send(new Admin2FAMail($admin, '123456'));

        Mail::assertSent(Admin2FAMail::class, function (Admin2FAMail $mail) {
            return $mail->hasFrom('contact@mekontso.gsi2026.com', 'EduPay Cameroun — Sécurité');
        });
    }
}
