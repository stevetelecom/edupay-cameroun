<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Etablissement;
use App\Models\ParametreSysteme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les deux pannes trouvees le 30/09/2026 sur la page Commission :
 *   - GET /commissions/{etablissement}/modifier renvoyait 500 (vue jamais
 *     creee : admin.commissions.edit n'existait pas sur disque) ;
 *   - le bouton « taux global » postait vers /commissions/global/modifier,
 *     capture par le segment {etablissement} : recherche d'un etablissement
 *     d'id "global", 404.
 */
class CommissionsRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $admin = Admin::create([
            'prenom' => 'S', 'nom' => 'Routes',
            'email' => 'routes-cms@test.cm', 'password' => bcrypt('secret1234'),
        ]);
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'admin');

        ParametreSysteme::definir([
            'taux_aangaraa' => '0.022',
            'marge_edupay'  => '0.001',
        ]);

        $this->etab = Etablissement::create([
            'code_etablissement' => 'ETAB-ROUTES',
            'nom'                => 'Ecole Routes',
            'email'              => 'routes@test.cm',
            'telephone'          => '690000002',
            'type'               => 'prive',
            'ville'              => 'Douala',
            'statut_juridique'   => 'prive_laic',
            'region'             => 'littoral',
            'taux_commission'    => 0.001,
        ]);
    }

    public function test_la_page_modifier_le_taux_se_rend_sans_erreur(): void
    {
        // La page ne propose plus de saisie : le taux se regle par profil
        // d'abonnement (CDC S0 #3). Elle doit donc s'afficher en expliquant
        // ou aller, sans champ taux_commission a remplir.
        $this->get(route('admin.commissions.edit', $this->etab))
            ->assertOk()
            ->assertSee('Ecole Routes')
            // Aucun champ de saisie ne doit subsister : le taux ne se regle
            // plus par etablissement.
            ->assertDontSee('name="taux_commission"', false);
    }

    /**
     * CDC S0 #3 : le taux se configure par profil d'abonnement, avec un
     * plancher impose (cout AangaraaPay). Sans ce garde-fou, un taux trop bas
     * ferait perdre de l'argent a la plateforme sur chaque reversement.
     */
    public function test_les_taux_par_profil_se_modifient_independamment(): void
    {
        $this->patch(route('admin.commissions.taux-plans'), [
            'taux_basique'  => 0.030,
            'taux_standard' => 0.035,
            'taux_premium'  => 0.040,
        ])->assertRedirect();

        $this->assertSame('0.03', (string) ParametreSysteme::obtenir('taux_commission_basique'));
        $this->assertSame('0.035', (string) ParametreSysteme::obtenir('taux_commission_standard'));
        $this->assertSame('0.04', (string) ParametreSysteme::obtenir('taux_commission_premium'));
    }

    public function test_un_taux_par_profil_inferieur_au_cout_aangaraa_est_refuse(): void
    {
        // 0,010 < 0,022 : la plateforme serait a perte sur chaque paiement.
        $avant = [
            'basique'  => ParametreSysteme::obtenir('taux_commission_basique'),
            'standard' => ParametreSysteme::obtenir('taux_commission_standard'),
            'premium'  => ParametreSysteme::obtenir('taux_commission_premium'),
        ];

        $this->patch(route('admin.commissions.taux-plans'), [
            'taux_basique'  => 0.010,
            'taux_standard' => 0.035,
            'taux_premium'  => 0.040,
        ])->assertSessionHasErrors('taux_basique');

        // Aucun plan n'est ecrit quand la validation echoue : on ne laisse
        // pas un lot a moitie applique.
        $this->assertSame($avant['basique'],  ParametreSysteme::obtenir('taux_commission_basique'));
        $this->assertSame($avant['standard'], ParametreSysteme::obtenir('taux_commission_standard'));
        $this->assertSame($avant['premium'],  ParametreSysteme::obtenir('taux_commission_premium'));
    }

    public function test_chaque_plan_dispose_de_son_propre_taux_pour_les_frais(): void
    {
        ParametreSysteme::definir([
            'taux_commission_basique'  => '0.030',
            'taux_commission_premium'  => '0.050',
        ]);

        $service = new \App\Services\AangaraaPayService();

        // Sans etablissement, on retombe sur le taux global (2,3 %).
        $this->assertSame(0.023, $service->tauxCommissionPlan(null));

        // Un plan hors bornes retombe aussi sur le taux global, jamais en deca.
        ParametreSysteme::definir(['taux_commission_premium' => '0.005']);
        $this->assertSame(0.023, $service->tauxCommissionPlan('premium'));

        ParametreSysteme::definir(['taux_commission_premium' => '0.050']);
        $this->assertSame(0.05, $service->tauxCommissionPlan('premium'));
        $this->assertSame(0.03, $service->tauxCommissionPlan('basique'));
    }

    public function test_le_taux_global_se_modifie_via_la_route_dediee(): void
    {
        $this->patch(route('admin.commissions.taux-global'), [
            'taux_global' => 0.026,
        ])->assertRedirect();

        // La part AangaraaPay reste inchangee, seule la marge EduPay bouge.
        $this->assertSame('0.022', ParametreSysteme::obtenir('taux_aangaraa'));
        $this->assertSame('0.004', (string) ParametreSysteme::obtenir('marge_edupay'));
    }

    public function test_un_taux_global_inferieur_au_cout_aangaraa_est_refuse(): void
    {
        // 0.010 < 0.022 : refuser plutot que de faire perdre de l'argent.
        $this->patch(route('admin.commissions.taux-global'), [
            'taux_global' => 0.010,
        ])->assertSessionHasErrors('taux_global');

        $this->assertSame('0.001', (string) ParametreSysteme::obtenir('marge_edupay'));
    }
}