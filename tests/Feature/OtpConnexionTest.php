<?php

namespace Tests\Feature;

use App\Mail\OtpConnexionMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Connexion par code OTP envoye par email.
 */
class OtpConnexionTest extends TestCase
{
    use RefreshDatabase;

    private function creerParent(string $email = 'parent.test@exemple.cm'): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('etablissement', 'web');

        return User::factory()->create([
            'email'         => $email,
            'password'      => Hash::make('Motdepasse1!'),
            'suspendu'      => false,
            'email_verified_at' => now(),
        ]);
    }

    /** Recupere le code OTP reellement transmis au mailer. */
    private function otpCapture(): string
    {
        $code = null;

        Mail::assertSent(OtpConnexionMail::class, function (OtpConnexionMail $mail) use (&$code) {
            $code = $mail->otpCode;

            return true;
        });

        return (string) $code;
    }

    /**
     * Roles disposant d'un espace web, et tableau de bord attendu apres
     * connexion par OTP. Le flux OTP ne filtre sur aucun role : ce que l'on
     * verifie ici, c'est que chaque profil atterrit bien sur SON espace et non
     * sur une page d'accueil qui ferait croire a un echec de connexion.
     */
    public static function rolesAvecEspaceWeb(): array
    {
        return [
            'directeur' => ['directeur', 'etablissement.dashboard'],
            'comptable' => ['comptable', 'etablissement.dashboard'],
            'caissier'  => ['caissier',  'etablissement.dashboard'],
            'parent'    => ['parent',    'payeur.dashboard'],
            'eleve'     => ['eleve',     'payeur.dashboard'],
        ];
    }

    #[DataProvider('rolesAvecEspaceWeb')]
    public function test_tout_profil_peut_se_connecter_par_otp(string $role, string $attendu): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create([
            'email'            => $role . '.otp@exemple.cm',
            'password'         => Hash::make('Motdepasse1!'),
            'suspendu'         => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        Mail::fake();

        $this->post(route('login.otp.verify'), ['login' => $user->email])
            ->assertSessionHas('otp_sent');

        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $this->otpCapture(),
        ])->assertRedirect(route($attendu));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * `etudiant` est un role API/mobile : il n'a pas d'espace web. La
     * connexion par OTP doit rester possible, mais l'utilisateur ne doit pas
     * atterrir sur une page qui casse. On verifie qu'il est authentifie et
     * renvoye vers une page existante.
     */
    public function test_un_role_sans_espace_web_est_tout_de_meme_authentifie(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('etudiant', 'web');

        $user = User::factory()->create([
            'email'            => 'etudiant.otp@exemple.cm',
            'password'         => Hash::make('Motdepasse1!'),
            'suspendu'         => false,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('etudiant');

        Mail::fake();

        $this->post(route('login.otp.verify'), ['login' => $user->email]);
        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $this->otpCapture(),
        ])->assertRedirect(route('landing'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_code_neuf_repart_de_zero_tentative(): void
    {
        $user = $this->creerParent();
        Mail::fake();

        // 1. Deux erreurs sur un PREMIER code. Le compteur vaut alors 2.
        $this->post(route('login.otp.verify'), ['login' => $user->email])
            ->assertSessionHas('otp_sent');

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('login.otp.verify'), [
                'login'    => $user->email,
                'otp_code' => '000000',
            ]);
        }

        // 2. L'utilisateur demande un NOUVEAU code. Ce code est valide et
        // recu par email : il doit offrir 3 essais, pas 1 seul.
        $this->post(route('login.otp.verify'), ['login' => $user->email])
            ->assertSessionHas('otp_sent');

        $nouveauCode = $this->otpCapture();

        // 3. Une seule faute de frappe sur ce nouveau code ne doit pas
        // declencher « Trop de tentatives » en heritant des 2 erreurs du
        // code precedent. Le message attendu est « code invalide ».
        $reponse = $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => '222222',
        ]);

        $reponse->assertSessionHas('error');
        $this->assertStringNotContainsString(
            'Trop de tentatives',
            (string) session('error'),
            'Le compteur de tentatives n\'a pas ete remis a zero par le nouveau code.'
        );

        // 4. Le code correct doit donc fonctionner.
        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $nouveauCode,
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_trois_codes_errones_bloquent_bien_la_connexion(): void
    {
        $user = $this->creerParent();
        Mail::fake();

        $this->post(route('login.otp.verify'), ['login' => $user->email]);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login.otp.verify'), [
                'login'    => $user->email,
                'otp_code' => '000000',
            ]);
        }

        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $this->otpCapture(),
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_le_code_expire_apres_cinq_minutes(): void
    {
        $user = $this->creerParent();
        Mail::fake();

        $this->post(route('login.otp.verify'), ['login' => $user->email])
            ->assertSessionHas('otp_sent');

        $code = $this->otpCapture();

        // On simule le passage du temps sans attendre reellement 5 minutes.
        $this->travel(6)->minutes();

        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $code,
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_un_compte_suspendu_ne_peut_pas_utiliser_un_code_valide(): void
    {
        $user = $this->creerParent();
        Mail::fake();

        $this->post(route('login.otp.verify'), ['login' => $user->email]);
        $code = $this->otpCapture();

        $user->forceFill(['suspendu' => true])->save();

        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $code,
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_le_jeton_otp_est_consomme_et_ne_peut_pas_etre_reutilise(): void
    {
        $user = $this->creerParent();
        Mail::fake();

        $this->post(route('login.otp.verify'), ['login' => $user->email]);
        $code = $this->otpCapture();

        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $code,
        ]);

        $this->assertAuthenticatedAs($user);

        // Un OTP doit servir UNE seule fois : le cache est vide apres succes.
        $this->assertNull(Cache::get('otp_connexion_' . $user->id));

        // Rejouer exactement le meme code, cote session vierge, ne doit rien
        // ouvrir. Sans le `Cache::forget` apres validation, ce code resterait
        // rejouable pendant toute la fenetre de 5 minutes.
        auth()->logout();
        $this->flushSession();

        $this->post(route('login.otp.verify'), [
            'login'    => $user->email,
            'otp_code' => $code,
        ])->assertSessionHas('error');

        $this->assertGuest();
    }
}
