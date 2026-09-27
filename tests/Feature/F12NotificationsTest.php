<?php

namespace Tests\Feature;

use App\Jobs\SendAlerteImpaye;
use App\Jobs\SendConfirmationPaiement;
use App\Mail\AlerteImpayelMail;
use App\Mail\ConfirmationPaiementMail;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Ces tests ne verifiaient rien : deux `assertTrue(true)` et quatre corps
 * `if ($paiement) { ... }` silencieusement ignores faute de donnees, donc
 * verts meme avec les notifications completement cassees. Reecrits sur des
 * fixtures reelles et de vraies assertions.
 */
class F12NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etablissement;

    private User $parent;

    private Apprenant $apprenant;

    private FraisApprenant $frais;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etablissement = Etablissement::create([
            'code_etablissement'     => 'F12-2026',
            'nom'                   => 'Ecole F12',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000004',
            'email'                 => 'f12@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);

        $this->parent = User::factory()->create([
            'email'            => 'parent.f12@test.cm',
            'notif_email'      => true,
            'notif_sms'        => true,
            'telephone'        => '691234567',
            'etablissement_id' => $this->etablissement->id,
        ]);

        $this->apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'matricule'                => 'F12-0001',
            'nom'                      => 'Fotso',
            'prenom'                   => 'Nadege',
            'classe'                   => '2nde',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
        $this->apprenant->parents()->attach($this->parent->id, ['lien' => 'parent']);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etablissement->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'actif'            => true,
            'annee_scolaire'   => '2026-2027',
        ]);

        $this->frais = FraisApprenant::create([
            'apprenant_id'       => $this->apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'annee_scolaire'     => '2026-2027',
            'montant_total'      => 50000,
            'montant_paye'       => 0,
            'statut'             => 'impaye',
        ]);
    }

    private function creerPaiement(string $statut = 'valide'): Paiement
    {
        return Paiement::create([
            'reference'          => 'PAY-F12-'.strtoupper(substr(md5($statut.microtime(true)), 0, 6)),
            'user_id'            => $this->parent->id,
            'apprenant_id'       => $this->apprenant->id,
            'frais_apprenant_id' => $this->frais->id,
            'montant'            => 50000,
            'mode_paiement'      => 'mobile_money',
            'statut'             => $statut,
            'date_paiement'      => '2026-09-20 10:00:00',
        ]);
    }

    private function smsFaux(): SmsService
    {
        return new class extends SmsService
        {
            public array $messages = [];

            public function envoyer(string $telephone, string $message): bool
            {
                $this->messages[] = ['telephone' => $telephone, 'message' => $message];

                return true;
            }

            public function envoyerRelance(string $telephone, string $message): bool
            {
                return $this->envoyer($telephone, $message);
            }
        };
    }

    public function test_le_job_de_confirmation_envoie_lemail_au_payeur()
    {
        Mail::fake();
        $paiement = $this->creerPaiement();

        (new SendConfirmationPaiement($paiement))->handle($this->smsFaux());

        Mail::assertSent(ConfirmationPaiementMail::class, function ($mail) use ($paiement) {
            return $mail->hasTo($this->parent->email) && $mail->paiement->is($paiement);
        });
    }

    public function test_le_job_de_confirmation_envoie_le_sms_si_il_y_en_a_un()
    {
        Mail::fake();
        $sms = $this->smsFaux();

        (new SendConfirmationPaiement($this->creerPaiement()))->handle($sms);

        $this->assertNotEmpty($sms->messages, 'aucun SMS envoye alors que le payeur a un numero');
        $this->assertSame('691234567', $sms->messages[0]['telephone']);
        $this->assertStringContainsString('PAY-F12', $sms->messages[0]['message']);
    }

    public function test_le_job_de_confirmation_respecte_le_preference_email()
    {
        Mail::fake();
        $this->parent->update(['notif_email' => false]);

        (new SendConfirmationPaiement($this->creerPaiement()))->handle($this->smsFaux());

        Mail::assertNotSent(ConfirmationPaiementMail::class);
    }

    public function test_le_job_de_confirmation_respecte_le_preference_sms()
    {
        Mail::fake();
        $sms = $this->smsFaux();
        $this->parent->update(['notif_sms' => false]);

        (new SendConfirmationPaiement($this->creerPaiement()))->handle($sms);

        $this->assertSame([], $sms->messages, 'SMS envoye alors que le payeur a desactive ses SMS');
    }

    public function test_le_job_alerte_impaye_envoie_un_email_par_parent()
    {
        Mail::fake();
        $secondParent = User::factory()->create([
            'email'            => 'parent.f12bis@test.cm',
            'notif_email'      => true,
            'telephone'        => '698765432',
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->apprenant->parents()->attach($secondParent->id, ['lien' => 'parent']);

        (new SendAlerteImpaye($this->apprenant, 'Scolarite', 50000, '23/06/2026'))
            ->handle($this->smsFaux());

        Mail::assertSent(AlerteImpayelMail::class, 2);
        Mail::assertSent(AlerteImpayelMail::class, fn ($mail) => $mail->hasTo($this->parent->email));
        Mail::assertSent(AlerteImpayelMail::class, fn ($mail) => $mail->hasTo($secondParent->email));
    }

    public function test_le_job_alerte_impaye_respecte_la_preference_email_du_parent()
    {
        Mail::fake();
        $muet = User::factory()->create([
            'email'            => 'parent.muet@test.cm',
            'notif_email'      => false,
            'telephone'        => null,
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->apprenant->parents()->attach($muet->id, ['lien' => 'parent']);

        (new SendAlerteImpaye($this->apprenant, 'Scolarite', 50000, '23/06/2026'))
            ->handle($this->smsFaux());

        Mail::assertSent(AlerteImpayelMail::class, 1);
        Mail::assertNotSent(AlerteImpayelMail::class, fn ($mail) => $mail->hasTo($muet->email));
    }

    public function test_le_scheduler_declenche_bien_les_deux_taches()
    {
        $this->artisan('schedule:list')->assertSuccessful();

        $evenements = collect(app(Schedule::class)->events())
            ->mapWithKeys(fn ($e) => [$e->description ?? '' => $e->expression]);

        $descriptions = $evenements->keys()->all();

        $this->assertSame('0 7 * * *', $evenements->get('sms-relance-impaye'),
            'la relance SMS J-5 (07:00) nest plus programmee : '.implode(' | ', $descriptions));
        $this->assertSame('0 18 * * *', $evenements->get('alerte-impaye-journaliere'),
            'lalerte impaye quotidienne (18:00) nest plus programmee');
    }
}
