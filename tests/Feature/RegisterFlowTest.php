<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Base SQLite :memory: fournie par phpunit.xml, et une session "array"
        // pour ne pas persister sur disque.
        config(['session.driver' => 'array']);
    }

    public function test_retour_conserve_step2_et_redirige_version_step1(): void
    {
        $step1 = $this->post('/inscription/etablissement/step1', [
            'nom'              => 'Lycee Test',
            'type'             => 'lycee_general',
            'statut_juridique' => 'prive_laic',
            'numero_agrement'  => 'AGR-123',
            'region'           => 'centre',
            'ville'            => 'Yaounde',
        ])->assertRedirect(route('register.ecole.step2'));

        $this->get('/inscription/etablissement/step2')->assertOk();

        // Simule l'appui sur « Retour » depuis l'étape 2 (saveAndBack)
        $this->post('/inscription/etablissement/save-back/2', [
            'resp_prenom'               => 'bobo',
            'resp_nom'                  => 'olive',
            'resp_telephone'            => '+237654862989',
            'resp_email'                => 'test@example.com',
            'resp_password'             => 'Rois@10720',
            'resp_password_confirmation'=> 'Rois@10720',
        ])->assertSessionHasNoErrors()
          ->assertRedirect(route('register.ecole.step1'));

        // Les infos de l'étape 2 doivent être conservées et le mot de passe hashed
        $this->assertNotEmpty(session('register_ecole.step2.resp_password'));
        $this->assertNotEquals('Rois@10720', session('register_ecole.step2.resp_password'));

        // On peut revenir à l'étape 2 (étape 1 finalisée) et les champs sont remplis
        $this->get('/inscription/etablissement/step2')
            ->assertOk()
            ->assertSee('bobo');
    }

    public function test_on_ne_peut_pas_acceder_step2_sans_finaliser_step1(): void
    {
        $this->get('/inscription/etablissement/step2')
            ->assertRedirect(route('register.ecole.step1'));

        $this->get('/inscription/etablissement/validation')
            ->assertRedirect(route('register.ecole.step1'));
    }
}
