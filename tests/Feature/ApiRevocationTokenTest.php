<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Audit S — revocation des tokens Sanctum.
 *
 * Trois fuites :
 *  - la suspension d'un compte n'etait verifiee qu'a la connexion : un token
 *    deja emis restait valable apres la suspension ;
 *  - le changement de mot de passe via l'API ne revoquait aucun token ;
 *  - la deconnexion appelait `delete()` sur `currentAccessToken()` sans
 *    verifier sa nature (cookie => TransientToken sans `delete()`, 500).
 */
class ApiRevocationTokenTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etablissement;

    private User $payeur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etablissement = Etablissement::create([
            'code_etablissement'     => 'EP-REVOC',
            'nom'                   => 'Ecole Revocation',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'revocation@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);

        $this->payeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
            'password'         => Hash::make('AncienMotdepasse1!'),
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'nom'                      => 'Fono',
            'prenom'                   => 'Chloe',
            'classe'                   => 'CM2',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        $apprenant->parents()->attach($this->payeur->id, ['lien' => 'parent']);
    }

    /**
     * Le guard `auth:sanctum` memorise l'utilisateur resolu pendant toute la
     * duree du test : sans cet oubli, la 2e requête de la suite reutilise
     * l'etat precedent (suspendu=true) et les tests mentent. En production
     * chaque requete HTTP repart d'un conteneur neuf, le middleware est donc
     * bien lu a chaque appel.
     */
    private function oublierLesGardes(): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
    }

    private function authentifier(): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('api.v1.auth.login'), [
            'identifiant' => $this->payeur->email,
            'password'    => 'AncienMotdepasse1!',
        ]);
    }

    // ─────────────────────────────────────────────
    // Suspension
    // ─────────────────────────────────────────────

    public function test_un_compte_suspendu_ne_peut_plus_utiliser_son_token()
    {
        $token = $this->authentifier()->assertOk()->json('data.token');

        // Avant suspension : l'API repond.
        $this->withToken($token)->getJson(route('api.v1.me'))->assertOk();

        $this->payeur->update([
            'suspendu'        => true,
            'suspendu_raison' => 'Tentative de fraude',
        ]);
        $this->oublierLesGardes();

        $reponse = $this->withToken($token)->getJson(route('api.v1.me'))->assertForbidden();

        $this->assertSame('compte_suspendu', $reponse->json('code'));
        $this->assertStringContainsString('Tentative de fraude', $reponse->json('message'));
    }

    public function test_la_suspension_revoque_les_tokens_du_compte()
    {
        $token = $this->authentifier()->assertOk()->json('data.token');

        $this->assertSame(1, $this->payeur->tokens()->count());

        $this->payeur->update(['suspendu' => true]);
        $this->oublierLesGardes();

        $this->withToken($token)->getJson(route('api.v1.me'))->assertForbidden();

        // Le token porte par la requete est coupe, il ne repasse pas.
        $this->assertSame(0, $this->payeur->tokens()->count());
    }

    public function test_un_compte_non_suspendu_n_est_pas_bloque()
    {
        $token = $this->authentifier()->assertOk()->json('data.token');

        $this->withToken($token)->getJson(route('api.v1.me'))->assertOk();
        $this->assertSame(1, $this->payeur->tokens()->count());
    }

    public function test_un_etablissement_suspendu_ne_bloque_pas_son_equipe()
    {
        // L'etablissement suspendu doit rester consultable (bandeau dedie).
        $token = $this->authentifier()->assertOk()->json('data.token');

        $this->etablissement->update(['statut' => 'suspendu']);
        $this->oublierLesGardes();

        $this->withToken($token)->getJson(route('api.v1.me'))->assertOk();
    }

    public function test_un_compte_suspendu_peut_tout_de_meme_se_deconnecter()
    {
        $token = $this->authentifier()->assertOk()->json('data.token');

        $this->payeur->update(['suspendu' => true]);
        $this->oublierLesGardes();

        $this->withToken($token)
            ->postJson(route('api.v1.auth.logout'))
            ->assertOk();
    }

    // ─────────────────────────────────────────────
    // Changement de mot de passe
    // ─────────────────────────────────────────────

    public function test_le_changement_de_mot_de_passe_revoque_les_sessions()
    {
        $tokenMobile = $this->authentifier()->assertOk()->json('data.token');
        $tokenWeb    = $this->payeur->createToken('web')->plainTextToken;

        $this->assertSame(2, $this->payeur->tokens()->count());

        $reponse = $this->withToken($tokenMobile)->putJson(route('api.v1.profil.password'), [
            'current_password' => 'AncienMotdepasse1!',
            'password'         => 'NouveauMotdepasse2@',
            'password_confirmation' => 'NouveauMotdepasse2@',
        ])->assertOk();

        $this->assertTrue($reponse->json('reconnexion_requise'));
        $this->assertSame(2, $reponse->json('sessions_revoquees'));

        // Les deux sessions sont coupees, y compris sur l'appareil qui vient
        // de changer le mot de passe : il doit se reconnecter.
        $this->assertSame(0, $this->payeur->tokens()->count());
        $this->oublierLesGardes();

        $this->withToken($tokenWeb)->getJson(route('api.v1.me'))->assertUnauthorized();
        $this->withToken($tokenMobile)->getJson(route('api.v1.me'))->assertUnauthorized();
    }

    public function test_un_mauvais_mot_de_passe_actuel_ne_revoque_rien()
    {
        $token = $this->authentifier()->assertOk()->json('data.token');

        $this->withToken($token)
            ->putJson(route('api.v1.profil.password'), [
                'current_password' => 'MauvaisMotdepasse1!',
                'password'         => 'NouveauMotdepasse2@',
                'password_confirmation' => 'NouveauMotdepasse2@',
            ])
            ->assertStatus(422);

        $this->assertSame(1, $this->payeur->tokens()->count());
    }

    // ─────────────────────────────────────────────
    // Deconnexion
    // ─────────────────────────────────────────────

    public function test_la_deconnexion_revoque_le_token_courant()
    {
        $token = $this->authentifier()->assertOk()->json('data.token');

        $reponse = $this->withToken($token)->postJson(route('api.v1.auth.logout'))->assertOk();

        $this->assertSame(1, $reponse->json('revoques'));
        $this->assertSame(0, $this->payeur->tokens()->count());
        $this->oublierLesGardes();

        $this->withToken($token)->getJson(route('api.v1.me'))->assertUnauthorized();
    }

    public function test_la_deconnexion_peut_revoquer_tous_les_appareils()
    {
        $tokenMobile = $this->authentifier()->assertOk()->json('data.token');
        $tokenWeb    = $this->payeur->createToken('web')->plainTextToken;

        $reponse = $this->withToken($tokenMobile)
            ->postJson(route('api.v1.auth.logout'), ['tous_appareils' => true])
            ->assertOk();

        $this->assertSame(2, $reponse->json('revoques'));
        $this->assertSame(0, $this->payeur->tokens()->count());
        $this->oublierLesGardes();

        $this->withToken($tokenWeb)->getJson(route('api.v1.me'))->assertUnauthorized();
    }

    public function test_la_deconnexion_ne_casse_pas_les_autres_sessions_par_defaut()
    {
        $tokenMobile = $this->authentifier()->assertOk()->json('data.token');
        $tokenWeb    = $this->payeur->createToken('web')->plainTextToken;

        $this->withToken($tokenMobile)->postJson(route('api.v1.auth.logout'))->assertOk();
        $this->oublierLesGardes();

        $this->withToken($tokenWeb)->getJson(route('api.v1.me'))->assertOk();
    }
}
