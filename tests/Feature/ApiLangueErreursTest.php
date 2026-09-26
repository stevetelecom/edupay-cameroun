<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit P — messages d'erreur FR / EN.
 *
 * SetLocale n'etait attache qu'au groupe `web` : l'API repondait toujours en
 * francais et `resources/lang/en` etait inatteignable pour le mobile. Les
 * messages centraux (401/403/404/422/429) etaient ecrits en dur.
 */
class ApiLangueErreursTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etablissement;

    private User $payeur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etablissement = Etablissement::create([
            'code_etablissement'     => 'LANG-2026',
            'nom'                   => 'Ecole Langue',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'langue@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);

        $this->payeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
        ]);

        Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'matricule'                => 'LANG-0001',
            'nom'                      => 'Kamdem',
            'prenom'                   => 'Ivo',
            'classe'                   => 'CM1',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
    }

    private function jeton(): string
    {
        return $this->payeur->createToken('mobile')->plainTextToken;
    }

    // ─────────────────────────────────────────────
    // 401 / 403 / 404 / 422
    // ─────────────────────────────────────────────

    public function test_401_en_francais_sans_parametre_de_langue()
    {
        $reponse = $this->getJson(route('api.v1.me'))->assertUnauthorized();

        $this->assertSame('Non authentifié. Un token valide est requis.', $reponse->json('message'));
    }

    public function test_le_parametre_lang_fr_impose_le_francais()
    {
        // ?lang= est prioritaire sur le cookie.
        // withCredentials() : en test, les cookies ne sont joints aux
        // requetes JSON que si les credentials sont actives.
        $reponse = $this->withCredentials()
            ->withUnencryptedCookie('locale', 'en')
            ->getJson(route('api.v1.me') . '?lang=fr')
            ->assertUnauthorized();

        $this->assertSame('Non authentifié. Un token valide est requis.', $reponse->json('message'));
    }

    public function test_401_en_anglais_avec_le_parametre_lang()
    {
        $reponse = $this->getJson(route('api.v1.me') . '?lang=en')->assertUnauthorized();

        $this->assertSame('Not authenticated. A valid token is required.', $reponse->json('message'));
    }

    public function test_401_en_anglais_avec_le_cookie_de_langue()
    {
        // withCredentials() : en test, les cookies ne sont joints aux
        // requetes JSON que si les credentials sont actives.
        $reponse = $this->withCredentials()
            ->withUnencryptedCookie('locale', 'en')
            ->getJson(route('api.v1.me'))
            ->assertUnauthorized();

        $this->assertSame('Not authenticated. A valid token is required.', $reponse->json('message'));
    }

    public function test_401_en_anglais_avec_un_cookie_de_langue_etranger()
    {
        // Un cookie pose par une autre onglet ne doit pas non plus
        // commander la langue d'une requete API isolee.
        // withCredentials() : en test, les cookies ne sont joints aux
        // requetes JSON que si les credentials sont actives.
        $reponse = $this->withCredentials()
            ->withUnencryptedCookie('locale', 'de')
            ->getJson(route('api.v1.me') . '?lang=en')
            ->assertUnauthorized();

        $this->assertSame('Not authenticated. A valid token is required.', $reponse->json('message'));
    }

    public function test_accept_language_ne_bascule_pas_la_langue_seule()
    {
        // Le changement de langue est explicite : un en-tete navigateur
        // Sending ne doit pas faire basculer les messages du back-office.
        $reponse = $this->withHeader('Accept-Language', 'en-US,en;q=0.5')
            ->getJson(route('api.v1.me'))
            ->assertUnauthorized();

        $this->assertSame('Non authentifié. Un token valide est requis.', $reponse->json('message'));
    }

    public function test_une_langue_non_supportee_retombe_sur_le_francais()
    {
        // withCredentials() : en test, les cookies ne sont joints aux
        // requetes JSON que si les credentials sont actives.
        $reponse = $this->withCredentials()
            ->withUnencryptedCookie('locale', 'de')
            ->getJson(route('api.v1.me'))
            ->assertUnauthorized();

        $this->assertSame('Non authentifié. Un token valide est requis.', $reponse->json('message'));
    }

    public function test_une_valeur_de_langue_invalide_est_ignoree()
    {
        $reponse = $this->getJson(route('api.v1.me') . '?lang=../../etc/passwd')->assertUnauthorized();

        $this->assertSame('Non authentifié. Un token valide est requis.', $reponse->json('message'));
    }

    public function test_403_traduit_en_anglais()
    {
        $reponse = $this->actingAs($this->payeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.dashboard') . '?lang=en')
            ->assertStatus(403);

        // Message metier specifique, traduit (et non le 403 generique).
        $this->assertSame('This account has no access to the school back-office.', $reponse->json('message'));
    }

    public function test_404_traduit_en_anglais()
    {
        $reponse = $this->actingAs($this->payeur, 'sanctum')
            ->getJson('/api/v1/une-route-inexistante?lang=en')
            ->assertStatus(404);

        $this->assertSame('Resource not found.', $reponse->json('message'));
    }

    public function test_422_messages_de_validation_en_anglais()
    {
        $reponse = $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher') . '?lang=en', [])
            ->assertStatus(422);

        // Le message retenu est la premiere erreur du validateur, en anglais
        // (resources/lang/en/validation.php) : le contrat 422 expose un
        // message exploitable par le mobile dans la langue demandee.
        $this->assertIsString($reponse->json('message'));
        $this->assertNotSame('The given data was invalid.', $reponse->json('message'));
        $this->assertNotEmpty($reponse->json('errors'));
    }

    public function test_422_messages_de_validation_en_francais()
    {
        $reponse = $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.apprenants.rattacher') . '?lang=fr', [])
            ->assertStatus(422);

        $this->assertStringContainsString('établissement', $reponse->json('message'));
        $this->assertNotEmpty($reponse->json('errors'));
    }

    public function test_429_traduit_en_anglais()
    {
        // /otp est limitee a 10/min : le 11e appel est refuse.
        $reponse = null;

        for ($i = 0; $i < 11; $i++) {
            $reponse = $this->postJson(route('api.v1.auth.otp') . '?lang=en', [
                'telephone' => '650000000',
            ]);
        }

        $reponse->assertStatus(429);
        $this->assertSame('Too many requests. Please try again shortly.', $reponse->json('message'));
    }

    public function test_compte_suspendu_traduit_en_anglais()
    {
        $token = $this->jeton();

        $this->payeur->update([
            'suspendu'        => true,
            'suspendu_raison' => 'Suspicion de fraude',
        ]);
        \Illuminate\Support\Facades\Auth::forgetGuards();

        $reponse = $this->withToken($token)->getJson(route('api.v1.me') . '?lang=en')->assertForbidden();

        $this->assertSame('This account is suspended: Suspicion de fraude', $reponse->json('message'));
    }
}
