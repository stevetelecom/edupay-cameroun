<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Connexion Google (Socialite).
 *
 * Le bug couvert ici est un 500 en production, difficile a reproduire parce
 * qu'il ne touchait QUE la premiere connexion Google d'un compte inconnu :
 *
 *   `users.password` est NOT NULL sans valeur par defaut, et
 *   GoogleController etait le SEUL chemin de creation de compte a ne pas
 *   fournir de mot de passe (RegisterParentController fait toujours
 *   Hash::make). L'INSERT echouait donc, et l'utilisateur voyait une erreur
 *   500. Un email deja present en base passait par la branche `else` et
 *   fonctionnait — d'ou l'impression que la connexion Google marchait
 *   "parfois".
 *
 * Ces tests verrouillent aussi le controle `email_verified` : sans lui, un
 * tiers拥有一ant un email Google non verifie pourrait prendre le controle
 * d'un compte EduPay existant, puisque le rattachement se fait par email.
 */
class ConnexionGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'parent']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Simule le retour de Google : un profil, verifie ou non.
     */
    private function fakeGoogle(string $email, string $googleId, ?bool $verifie = true): void
    {
        $profile = new SocialiteUser();
        $profile->id      = $googleId;
        $profile->name    = 'Jean Ntoutoume';
        $profile->email   = $email;
        $profile->user    = ['email_verified' => $verifie];

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($profile);

        $socialite = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $socialite->shouldReceive('user')->andReturn($profile);

        Socialite::shouldReceive('driver')->with('google')->andReturn($socialite);
    }

    /**
     * L'URI de retour doit pointer sur l'hote qui a emis la demande. Sinon
     * Google renvoie le navigateur sur un AUTRE serveur, dont la session ne
     * contient pas le `state` : la connexion echoue avec « Connexion Google
     * annulee » et AUCUNE trace n'apparait dans le log du serveur qui a
     * lance la demande. Ce scenario a rendu un bug de configuration
     * totalement invisible en developpement.
     */
    public function test_une_uri_de_retour_qui_pointe_ailleurs_est_signalee_dans_le_log(): void
    {
        config([
            'services.google.client_id'     => 'test-id',
            'services.google.client_secret' => 'test-secret',
            // Simule le .env local qui pointait sur le domaine de production.
            'services.google.redirect'      => 'https://edupay.exemple.com/auth/google/callback',
        ]);

        Log::spy();

        $this->get(route('login.google'))->assertRedirect();

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $contexte = []) {
                return str_contains($message, 'autre hote')
                    && ($contexte['hote_callback'] ?? null) === 'edupay.exemple.com';
            });
    }

    public function test_aucun_avertissement_quand_le_callback_est_bien_sur_le_meme_hote(): void
    {
        config([
            'services.google.client_id'     => 'test-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect'      => rtrim(config('app.url'), '/') . '/auth/google/callback',
        ]);

        Log::spy();

        $this->get(route('login.google'))->assertRedirect();

        // On filtre sur NOTRE message : l'environnement de test leve d'autres
        // avertissements sans rapport (tables absentes en sqlite in-memory).
        Log::shouldNotHaveReceived('warning', function (string $message) {
            return str_contains($message, 'autre hote');
        });
    }

    public function test_la_premiere_connexion_google_cree_le_compte_au_lieu_de_renvoyer_500(): void
    {
        $this->fakeGoogle('parent.nouveau@gmail.com', 'google-id-1');

        $response = $this->get(route('login.google.callback'));

        // Le point central : plus de 500.
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email'     => 'parent.nouveau@gmail.com',
            'google_id' => 'google-id-1',
            'profil'    => 'parent',
        ]);

        // Le compte est connecte et parent du payeur.
        $this->assertTrue(Auth::check());
        $this->assertTrue(Auth::user()->hasRole('parent'));

        $this->assertSame(
            'Jean',
            Auth::user()->prenom,
            'Le prenom doit venir du nom Google.'
        );
        $this->assertSame('Ntoutoume', Auth::user()->nom);
    }

    public function test_le_mot_de_passe_genere_est_inexploitable_en_connexion_classique(): void
    {
        $this->fakeGoogle('parent.hasard@gmail.com', 'google-id-2');

        $this->get(route('login.google.callback'))->assertRedirect();

        $user = User::where('email', 'parent.hasard@gmail.com')->firstOrFail();

        // La colonne est NOT NULL : elle est bien remplie...
        $this->assertNotEmpty($user->password);

        // ...mais aucun mot de passe ne doit permettre de se connecter.
        $this->assertFalse(
            Auth::validate(['email' => $user->email, 'password' => 'password']),
            'Un compte Google ne doit pas etre connectable avec un mot de passe devine.'
        );
    }

    public function test_un_email_google_non_verifie_est_refuse(): void
    {
        $this->fakeGoogle('pirate@exemple.cm', 'google-id-3', false);

        $response = $this->get(route('login.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');

        $this->assertFalse(Auth::check());
        $this->assertDatabaseMissing('users', ['email' => 'pirate@exemple.cm']);
    }

    public function test_un_compte_existant_par_email_est_rattache_sans_creer_de_doublon(): void
    {
        $existant = User::create([
            'prenom'   => 'Marie',
            'nom'      => 'Mbarga',
            'email'    => 'marie.mbarga@gmail.com',
            'profil'   => 'parent',
            'password' => Hash::make('MotDePasseSolide123'),
        ]);
        $existant->assignRole('parent');

        $this->fakeGoogle('marie.mbarga@gmail.com', 'google-id-4');

        $this->get(route('login.google.callback'))->assertRedirect();

        $this->assertSame(1, User::where('email', 'marie.mbarga@gmail.com')->count());

        $existant->refresh();
        $this->assertSame('google-id-4', $existant->google_id);

        // Le mot de passe existant est preserve : l'utilisateur peut
        // toujours se connecter des deux facons.
        $this->assertTrue(
            Auth::validate(['email' => $existant->email, 'password' => 'MotDePasseSolide123'])
        );
    }

    public function test_un_compte_suspendu_est_refuse_meme_avec_un_google_valide(): void
    {
        $suspendu = User::create([
            'prenom'   => 'Paul',
            'nom'      => 'Atangana',
            'email'    => 'paul.atangana@gmail.com',
            'profil'   => 'parent',
            'password' => Hash::make('MotDePasseSolide123'),
            'suspendu' => true,
        ]);
        $suspendu->assignRole('parent');

        $this->fakeGoogle('paul.atangana@gmail.com', 'google-id-5');

        $response = $this->get(route('login.google.callback'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
    }
}
