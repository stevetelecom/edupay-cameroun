<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Vérifie que l'API renvoie bien les en-têtes CORS.
 *
 * Sans `config/cors.php`, le middleware global `HandleCors` s'exécute mais
 * `config('cors')` vaut `[]` : `allowedOrigins` reste vide et la réponse part
 * sans `Access-Control-Allow-Origin`. Le symptôme côté client est un blocage
 * CORS alors que la réponse HTTP est parfaitement correcte — l'application
 * mobile React Native ne peut plus se connecter du tout.
 *
 * Ces tests vérifient l'en-tête sur une vraie route de l'API, pas le
 * middleware isolé : c'est l'intégration qui avait regressé.
 */
class CorsApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Origine utilisée par l'app Expo en développement. Le port change selon
     * le mode de lancement, d'où l'importance de ne pas figer une liste.
     */
    private const ORIGINE_EXPO = 'http://localhost:8099';

    public function test_une_requete_api_avec_entete_origin_recoit_l_entete_cors(): void
    {
        $reponse = $this->withHeaders(['Origin' => self::ORIGINE_EXPO])
            ->postJson('/api/v1/auth/login', [
                'login'    => 'absent@example.test',
                'password' => 'mauvais-mot-de-passe',
            ]);

        $this->assertNotNull(
            $reponse->headers->get('Access-Control-Allow-Origin'),
            "L'en-tête Access-Control-Allow-Origin est absent : l'app mobile sera bloquée par CORS."
        );
    }

    /**
     * Le preflight (OPTIONS) est la requête que le navigateur envoie avant le
     * POST. Elle doit exposer `Authorization`, sinon le jeton Bearer est
     * refusé avant d'atteindre la route.
     */
    public function test_le_preflight_options_est_accepte_et_expose_authorization(): void
    {
        $reponse = $this->withHeaders([
            'Origin'                         => self::ORIGINE_EXPO,
            'Access-Control-Request-Method'  => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('/api/v1/auth/login');

        $this->assertSame(204, $reponse->getStatusCode());
        $this->assertNotNull($reponse->headers->get('Access-Control-Allow-Origin'));

        $autorisees = strtolower((string) $reponse->headers->get('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('authorization', $autorisees);
    }

    /**
     * Une réponse d'authentification valide doit elle aussi porter l'en-tête :
     * une réponse 200 sans en-tête échoue au moment de la lecture du corps.
     */
    public function test_une_reponse_d_authentification_reussie_porte_elle_aussi_l_entete(): void
    {
        Role::firstOrCreate(['name' => 'eleve', 'guard_name' => 'web']);

        $utilisateur = \App\Models\User::factory()->create([
            'email'    => 'cors-ok@example.test',
            'password' => bcrypt('MotDePasseTest123!'),
        ]);
        $utilisateur->assignRole('eleve');

        $reponse = $this->withHeaders(['Origin' => self::ORIGINE_EXPO])
            ->postJson('/api/v1/auth/login', [
                'login'    => 'cors-ok@example.test',
                'password' => 'MotDePasseTest123!',
            ]);

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertNotNull($reponse->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Garde-fou : si quelqu'un réintroduit `supports_credentials => true` avec
     * `allowed_origins => ['*']`, la spécification CORS l'interdit et le
     * navigateur refuse l'en-tête. On préfère le voir ici qu'en production.
     */
    public function test_la_configuration_cors_est_internellement_coherente(): void
    {
        $cors = config('cors');

        $this->assertIsArray($cors, 'config/cors.php doit exister et renvoyer un tableau');
        $this->assertNotEmpty($cors['allowed_origins'], 'Aucune origine autorisée : toutes les requêtes cross-origin échoueront');

        $toutes = $cors['allowed_origins'] === ['*'];
        $this->assertFalse(
            $toutes && $cors['supports_credentials'],
            "'*' avec supports_credentials à true est interdit par la spécification CORS."
        );
    }
}