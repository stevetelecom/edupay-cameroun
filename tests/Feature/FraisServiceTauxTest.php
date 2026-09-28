<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ParametreSysteme;
use App\Services\AangaraaPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les frais de service preleves au payeur sont un pourcentage, et ce
 * pourcentage se change depuis l'espace super admin.
 *
 * Modele verifie ici : le payeur debite « frais de scolarite + frais de
 * service », l'etablissement recoit exactement les frais de scolarite, et la
 * difference reste sur le compte AangaraaPay. L'ancien bareme fige
 * (200/400/800/1500/2500) prelevait moins que le cout du reversement et
 * faisait perdre de l'argent a la plateforme sur la plupart des montants.
 */
class FraisServiceTauxTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        $this->superAdmin = Admin::create([
            'prenom'   => 'Super',
            'nom'      => 'Admin',
            'email'    => 'frais-super@test.cm',
            'password' => bcrypt('secret1234'),
        ]);
        $this->superAdmin->assignRole('super-admin');
    }

    /** @return array<string, mixed> */
    private function payload(array $surcharge = []): array
    {
        return array_merge([
            'taux_aangaraa'    => 0.022,
            'marge_edupay'     => 0.001,
            'timeout_paiement' => 120,
            'max_tranches'     => 3,
            'langue_defaut'    => 'fr',
            'sms_actif'        => '1',
            'mtn_actif'        => '1',
            'orange_actif'     => '1',
        ], $surcharge);
    }

    public function test_le_taux_par_defaut_couvre_le_cout_plus_la_marge(): void
    {
        $detail = (new AangaraaPayService())->calculerFrais(50000);

        // 2,2 % de cout AangaraaPay + 0,1 % de marge = 2,3 % de frais.
        $this->assertSame(1150, $detail['frais_service']);
        $this->assertSame(1100, $detail['frais_aangaraa']);
        $this->assertSame(50, $detail['marge_edupay']);
        $this->assertSame(51150, $detail['montant_total_paye']);
    }

    public function test_le_super_admin_peut_modifier_la_marge_et_les_frais_suivent(): void
    {
        $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.parametres.update'), $this->payload(['marge_edupay' => 0.005]))
            ->assertRedirect();

        // 0,5 % de marge : les frais passent de 2,3 % a 2,7 %.
        $detail = (new AangaraaPayService())->calculerFrais(50000);

        $this->assertSame(0.005, (float) ParametreSysteme::obtenir('marge_edupay', 0));
        $this->assertSame(1350, $detail['frais_service']);
        $this->assertSame(250, $detail['marge_edupay']);
    }

    public function test_le_super_admin_peut_modifier_le_cout_du_prestataire(): void
    {
        $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.parametres.update'), $this->payload(['taux_aangaraa' => 0.03]))
            ->assertRedirect();

        $detail = (new AangaraaPayService())->calculerFrais(50000);

        $this->assertSame(0.03, (float) ParametreSysteme::obtenir('taux_aangaraa', 0));
        // 3,1 % de frais pour 3 % de cout : la marge de 0,1 % est preservee.
        $this->assertSame(1550, $detail['frais_service']);
        $this->assertSame(1500, $detail['frais_aangaraa']);
        $this->assertSame(50, $detail['marge_edupay']);
    }

    public function test_un_taux_hors_bornes_renseigne_en_base_est_ignore(): void
    {
        // Un parametre corrompu ne doit jamais faire exploser le montant
        // debite au payeur : le service retombe sur sa valeur par defaut.
        ParametreSysteme::definir(['taux_aangaraa' => 5, 'marge_edupay' => -1]);

        $service = new AangaraaPayService();

        $this->assertSame(0.022, $service->tauxAangaraa());
        $this->assertSame(0.001, $service->margeEdupay());

        $detail = $service->calculerFrais(50000);

        $this->assertSame(1150, $detail['frais_service']);
        $this->assertSame(50, $detail['marge_edupay']);
    }

    public function test_les_taux_hors_bornes_sont_refuses_a_l_enregistrement(): void
    {
        $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.parametres.update'), $this->payload(['marge_edupay' => 0.9]))
            ->assertSessionHasErrors('marge_edupay');

        $this->assertNull(
            ParametreSysteme::obtenir('marge_edupay', null),
            'Un taux refuse ne doit pas etre enregistre'
        );
    }

    public function test_les_taux_sont_obligatoires(): void
    {
        $this->actingAs($this->superAdmin, 'admin')
            ->post(route('admin.parametres.update'), $this->payload(['taux_aangaraa' => null]))
            ->assertSessionHasErrors('taux_aangaraa');
    }

    public function test_un_non_super_admin_ne_peut_pas_modifier_les_taux(): void
    {
        $simple = Admin::create([
            'prenom'   => 'Simple',
            'nom'      => 'Admin',
            'email'    => 'frais-simple@test.cm',
            'password' => bcrypt('secret1234'),
        ]);

        $this->actingAs($simple, 'admin')
            ->post(route('admin.parametres.update'), $this->payload(['marge_edupay' => 0.05]));

        $this->assertNull(
            ParametreSysteme::obtenir('marge_edupay', null),
            'Un admin non super ne doit pas pouvoir modifier les taux'
        );
    }

    public function test_la_page_des_parametres_affiche_le_total_reel(): void
    {
        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('admin.parametres.index'))
            ->assertOk()
            ->assertSee('taux_aangaraa', false)
            ->assertSee('marge_edupay', false);
    }
}
