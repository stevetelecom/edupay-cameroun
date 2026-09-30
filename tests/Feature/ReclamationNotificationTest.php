<?php

namespace Tests\Feature;

use App\Jobs\NotifierNouvelleReclamation;
use App\Jobs\NotifierReponseReclamation;
use App\Mail\AccuseReclamationMail;
use App\Mail\NouvelleReclamationMail;
use App\Mail\ReponseReclamationMail;
use App\Models\Admin;
use App\Models\NotificationAdmin;
use App\Models\NotificationPayeur;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Échanges admin <-> payeur autour d'un ticket de réclamation.
 *
 * Avant ce lot, une réclamation n'écrivait aucun email : le payeur n'avait
 * aucune trace d'avoir été entendu et le ticket dormait dans le back-office.
 * Ces tests verrouillent les trois canaux et, surtout, l'indépendance entre
 * eux : un SMTP en panne ne doit jamais faire perdre le ticket.
 */
class ReclamationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $payeur;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        $this->admin = Admin::create([
            'prenom' => 'Steve', 'nom' => 'Admin',
            'email' => 'admin@test.cm', 'password' => bcrypt('secret1234'),
        ]);
        $this->admin->assignRole('super-admin');

        $this->payeur = User::factory()->create([
            'email' => 'parent@test.cm',
            'notif_email' => true,
        ]);
    }

    private function reclamation(array $attrs = []): Reclamation
    {
        return Reclamation::create(array_merge([
            'user_id'     => $this->payeur->id,
            'sujet'       => 'Retard de paiement',
            'description' => 'Le paiement n’apparaît pas sur mon relevé.',
            'statut'      => 'ouvert',
        ], $attrs));
    }

    // ── Ouverture : alerte support + accusé de réception ──

    public function test_ouvrir_une_reclamation_declenche_les_deux_emails(): void
    {
        Mail::fake();

        $reclamation = $this->reclamation();

        (new NotifierNouvelleReclamation($reclamation))->handle();

        // 1. L'alerte part sur l'adresse de contact, avec le payeur en
        //    reply-to : c'est ce qui permet de répondre au ticket par email.
        Mail::assertSent(NouvelleReclamationMail::class, function ($mail) {
            $this->assertTrue($mail->hasTo(config('mail.contact_address')));
            $this->assertTrue($mail->hasReplyTo($this->payeur->email));

            return true;
        });

        // 2. Le payeur reçoit un accusé portant son numéro de ticket.
        Mail::assertSent(AccuseReclamationMail::class, function ($mail) {
            $this->assertTrue($mail->hasTo($this->payeur->email));
            $this->assertStringContainsString(
                $mail->reclamation->numero_ticket,
                (string) $mail->render()
            );

            return true;
        });
    }

    public function test_ouvrir_une_reclamation_allume_la_cloche_admin(): void
    {
        Mail::fake();

        $reclamation = $this->reclamation();

        (new NotifierNouvelleReclamation($reclamation))->handle();

        $this->assertSame(1, NotificationAdmin::where('admin_id', $this->admin->id)->count());
        $this->assertDatabaseHas('notifications_admin', [
            'admin_id'       => $this->admin->id,
            'reclamation_id' => $reclamation->id,
            'type'           => 'reclamation',
            'lu_at'          => null,
        ]);
    }

    public function test_le_compteur_de_la_cloche_ignore_les_notifications_lues(): void
    {
        Mail::fake();

        $r1 = $this->reclamation();
        $r2 = $this->reclamation();

        (new NotifierNouvelleReclamation($r1))->handle();
        (new NotifierNouvelleReclamation($r2))->handle();

        $this->assertSame(2, NotificationAdmin::where('admin_id', $this->admin->id)->whereNull('lu_at')->count());

        NotificationAdmin::first()->update(['lu_at' => now()]);

        $this->assertSame(1, NotificationAdmin::where('admin_id', $this->admin->id)->whereNull('lu_at')->count());
    }

    public function test_une_panne_du_smtp_ne_perd_pas_la_reclamation(): void
    {
        // L'alerte au support échoue, l'accusé au payeur part quand même.
        Mail::shouldReceive('to')->andReturnUsing(function ($dest) {
            $mock = \Mockery::mock();
            if (is_string($dest) && str_contains($dest, 'contact')) {
                $mock->shouldReceive('send')->andThrow(new \RuntimeException('SMTP down'));
            } else {
                $mock->shouldReceive('send')->andReturnNull();
            }

            return $mock;
        });

        $reclamation = $this->reclamation();

        (new NotifierNouvelleReclamation($reclamation))->handle();

        $this->assertDatabaseHas('reclamations', ['id' => $reclamation->id]);
        // La cloche s'allume même si l'email d'alerte n'est pas parti.
        $this->assertSame(1, NotificationAdmin::count());
    }

    // ── Réponse : email au payeur + notification in-app ──

    public function test_la_reponse_de_l_admin_email_et_allume_la_cloche_du_payeur(): void
    {
        Mail::fake();

        $reclamation = $this->reclamation();
        $reclamation->update([
            'reponse_admin' => 'Nous avons régularisé votre paiement.',
            'statut'        => 'resolu',
            'resolu_le'     => now(),
        ]);

        (new NotifierReponseReclamation($reclamation->fresh()))->handle();

        Mail::assertSent(ReponseReclamationMail::class, function ($mail) {
            $this->assertTrue($mail->hasTo($this->payeur->email));
            // Le payeur répond au support, pas à l'admin signataire.
            $this->assertTrue($mail->hasReplyTo(config('mail.contact_address')));

            return true;
        });

        $this->assertDatabaseHas('notifications_payeur', [
            'user_id' => $this->payeur->id,
            'type'    => 'success',
            'lu_at'   => null,
        ]);
    }

    public function test_un_rejet_est_signale_comme_avertissement_et_non_comme_succes(): void
    {
        Mail::fake();

        $reclamation = $this->reclamation();
        $reclamation->update([
            'reponse_admin' => 'Dossier irrecevable.',
            'statut'        => 'rejete',
        ]);

        (new NotifierReponseReclamation($reclamation->fresh()))->handle();

        $this->assertDatabaseHas('notifications_payeur', [
            'user_id' => $this->payeur->id,
            'type'    => 'warning',
        ]);
    }

    public function test_la_notification_du_payeur_reprend_un_extrait_de_la_reponse(): void
    {
        Mail::fake();

        $reclamation = $this->reclamation();
        $reclamation->update(['reponse_admin' => str_repeat('a', 300), 'statut' => 'resolu']);

        (new NotifierReponseReclamation($reclamation->fresh()))->handle();

        $message = NotificationPayeur::first()->message;
        $this->assertLessThanOrEqual(140, mb_strlen($message));
        $this->assertStringContainsString('Résolu', $message);
    }

    // ── Routage : les deux points d'entrée dispatchent bien ──

    public function test_l_api_dispatch_la_notification_a_l_ouverture(): void
    {
        Bus::fake();

        $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.reclamations.store'), [
                'sujet'       => 'Question',
                'description' => 'Bonjour, une question sur mon fils.',
            ])
            ->assertCreated();

        Bus::assertDispatched(NotifierNouvelleReclamation::class);
    }

    public function test_repondre_a_un_ticket_dispatch_la_notification_au_payeur(): void
    {
        Bus::fake();

        $reclamation = $this->reclamation();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.reclamations.repondre', $reclamation), [
                'reponse_admin' => 'Merci de votre message.',
                'statut'        => 'resolu',
            ])
            ->assertRedirect();

        Bus::assertDispatched(NotifierReponseReclamation::class);
    }

    public function test_un_payeur_sans_email_ne_casse_pas_la_reponse(): void
    {
        Mail::fake();

        $this->payeur->update(['email' => null]);
        $reclamation = $this->reclamation();
        $reclamation->update(['reponse_admin' => 'Réponse.', 'statut' => 'resolu']);

        (new NotifierReponseReclamation($reclamation->fresh()))->handle();

        // La notification in-app existe toujours : le payeur voit la réponse
        // dans l'application même sans adresse mail.
        $this->assertSame(1, NotificationPayeur::where('user_id', $this->payeur->id)->count());
    }

    // ── Cloche admin : routes ──

    public function test_le_super_admin_voit_sa_file_de_notifications(): void
    {
        Mail::fake();

        $reclamation = $this->reclamation();
        (new NotifierNouvelleReclamation($reclamation))->handle();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('ep-bell-badge')
            // Le ticket est listé, pas seulement la cloche.
            ->assertSee($reclamation->numero_ticket)
            ->assertSee($reclamation->sujet);
    }

    public function test_la_cloche_du_header_badgele_le_compteur(): void
    {
        Mail::fake();

        (new NotifierNouvelleReclamation($this->reclamation()))->handle();
        (new NotifierNouvelleReclamation($this->reclamation()))->handle();

        // Le layout admin est partage : la cloche doit s'afficher sur une
        // page qui ne connait rien des notifications.
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ep-bell', $html);
        $this->assertStringContainsString('ep-bell-badge', $html);
        $this->assertStringContainsString('>2<', $html, 'le badge doit afficher 2');
    }

    public function test_la_cloche_disparait_quand_il_n_y_a_plus_rien(): void
    {
        Mail::fake();

        (new NotifierNouvelleReclamation($this->reclamation()))->handle();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.notifications.toutLu'))
            ->assertRedirect();

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        // L'icone reste, la pastille disparait.
        $this->assertStringContainsString('ep-bell', $html);
        $this->assertStringNotContainsString('ep-bell-badge', $html);
    }

    public function test_la_cloche_ne_sallit_pas_la_page_de_connexion(): void
    {
        // Pas d'admin connecte : pas de compteur, pas de fuite d'information.
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee('ep-bell-badge');
    }

    public function test_marquer_lu_eteint_la_pastille(): void
    {
        Mail::fake();

        (new NotifierNouvelleReclamation($this->reclamation()))->handle();
        $notification = NotificationAdmin::first();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.notifications.lu', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->lu_at);
    }

    public function test_un_admin_ne_peut_pas_lire_la_notification_d_un_autre(): void
    {
        Mail::fake();

        (new NotifierNouvelleReclamation($this->reclamation()))->handle();
        $notification = NotificationAdmin::first();

        $autre = Admin::create([
            'prenom' => 'Autre', 'nom' => 'Admin',
            'email' => 'autre@test.cm', 'password' => bcrypt('secret1234'),
        ]);

        $this->actingAs($autre, 'admin')
            ->patch(route('admin.notifications.lu', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->lu_at);
    }

    public function test_tout_marquer_lu_ne_touche_que_ses_propres_notifications(): void
    {
        Mail::fake();

        (new NotifierNouvelleReclamation($this->reclamation()))->handle();

        $autre = Admin::create([
            'prenom' => 'Autre', 'nom' => 'Admin',
            'email' => 'autre@test.cm', 'password' => bcrypt('secret1234'),
        ]);
        $sienne = NotificationAdmin::create([
            'admin_id' => $autre->id,
            'type'     => 'info',
            'titre'    => 'Autre chose',
            'message'  => 'Test',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.notifications.toutLu'))
            ->assertRedirect();

        $this->assertNull($sienne->fresh()->lu_at);
        $this->assertSame(0, NotificationAdmin::where('admin_id', $this->admin->id)->whereNull('lu_at')->count());
    }

    public function test_ouvrir_une_reclamation_et_repondre_ne_jourent_pas_en_file(): void
    {
        // Les jobs sont après commit : un rollback ne doit rien notifier.
        $this->assertTrue((new NotifierNouvelleReclamation($this->reclamation()))->afterCommit);
        $this->assertTrue((new NotifierReponseReclamation($this->reclamation()))->afterCommit);
    }
}
