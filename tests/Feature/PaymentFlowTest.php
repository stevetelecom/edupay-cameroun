<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Etablissement;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\Commission;
use App\Services\AangaraaPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Mockery;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_initier_and_validate_payment_flow_with_mocked_aangaraa()
    {
        // Préparation des données minimales
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'parent']);
        $user->assignRole('parent');

        $etab = Etablissement::create([
            'code_etablissement' => 'ETAB123',
            'nom' => 'Ecole Test',
            'type' => 'lycee_general',
            'statut_juridique' => 'prive_laic',
            'region' => 'centre',
            'ville' => 'Yaounde',
            'telephone' => '650000000',
            'email' => 'admin@ecole.test',
            'taux_commission' => 0.05,
            'numero_momo_reversement' => '650000000',
            'operateur_momo_reversement' => 'mtn',
            'statut' => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id' => $etab->id,
            'nom' => 'Eleve',
            'prenom' => 'Test',
            'classe' => '1ere',
            'statut_paiement' => 'impaye',
            'actif' => true,
        ]);

        $user->apprenants()->attach($apprenant->id, ['lien' => 'parent']);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etab->id,
            'nom' => 'Frais Scolarite',
            'montant_total' => 50000,
            'fractionnable' => true,
            'nb_tranches_max' => 2,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id' => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total' => 50000,
            'montant_paye' => 0,
            'statut' => 'impaye',
        ]);

        // Mock AangaraaPayService
        $mock = Mockery::mock(AangaraaPayService::class);

        // calculerFrais doit renvoyer le détail des frais
        $mock->shouldReceive('calculerFrais')->andReturnUsing(function ($montant) {
            $fraisVisibles = 200;
            $fraisAangaraa = (int) round($montant * 0.02);
            return [
                'montant_frais' => $montant,
                'frais_service' => $fraisVisibles,
                'frais_aangaraa' => $fraisAangaraa,
                'marge_edupay' => max(0, $fraisVisibles - $fraisAangaraa),
                'montant_total_paye' => $montant + $fraisVisibles,
            ];
        });

        $mock->shouldReceive('normaliserNumero')->andReturnUsing(function (string $telephone) {
            $service = new AangaraaPayService();

            return $service->normaliserNumero($telephone);
        });

        $mock->shouldReceive('initierPaiement')->andReturn([
            'succes'    => true,
            'pay_token' => 'MOCK_TOKEN_123',
            'statut'    => 'PENDING',
            'operateur' => 'MTN_Cameroon',
            'message'   => 'OK',
        ]);

        // verifierStatut renvoie SUCCESSFUL lors du poll
        $mock->shouldReceive('verifierStatut')->andReturn([
            'statut' => 'SUCCESSFUL',
            'succes' => true,
            'message' => 'OK',
        ]);

        // reverserEtablissement simule un reversement réussi
        $mock->shouldReceive('reverserEtablissement')->andReturn([
            'succes' => true,
            'reference' => 'REV123',
        ]);

        $this->app->instance(AangaraaPayService::class, $mock);

        // Exécuter l'initiation de paiement en tant que payeur
        $this->actingAs($user)
            ->post(route('payeur.paiement.initier', $frais), [
                'type_paiement' => 'integral',
                'mode_paiement' => 'mtn_momo',
                'telephone_paiement' => '650000000',
            ])
            ->assertRedirect();

        // Vérifier qu'un Paiement en_attente a été créé et contient le pay_token
        $paiement = Paiement::first();
        $this->assertNotNull($paiement);
        $this->assertEquals('en_attente', $paiement->statut);
        $this->assertEquals('MOCK_TOKEN_123', $paiement->pay_token);

        // Simuler le poll AJAX de vérification du statut
        $this->actingAs($user)
            ->get(route('payeur.paiement.statut', $paiement))
            ->assertJson(['statut' => 'valide']);

        $paiement->refresh();
        $this->assertEquals('valide', $paiement->statut);

        // Vérifier qu'une commission a été créée et que le reversement a été marqué
        $commission = Commission::where('paiement_id', $paiement->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals('prelevee', $commission->statut);
        $this->assertNotNull($commission->reference_reversement);
    }

    public function test_webhook_validates_payment_and_triggers_reversement_with_mocked_aangaraa()
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'parent']);
        $user->assignRole('parent');

        $etab = Etablissement::create([
            'code_etablissement' => 'ETAB456',
            'nom' => 'Ecole Test 2',
            'type' => 'lycee_general',
            'statut_juridique' => 'prive_laic',
            'region' => 'centre',
            'ville' => 'Yaounde',
            'telephone' => '650000001',
            'email' => 'admin2@ecole.test',
            'taux_commission' => 0.05,
            'numero_momo_reversement' => '650000001',
            'operateur_momo_reversement' => 'mtn',
            'statut' => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id' => $etab->id,
            'nom' => 'Eleve2',
            'prenom' => 'Test2',
            'classe' => '2eme',
            'statut_paiement' => 'impaye',
            'actif' => true,
        ]);

        $user->apprenants()->attach($apprenant->id, ['lien' => 'parent']);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etab->id,
            'nom' => 'Frais Scolarite 2',
            'montant_total' => 30000,
            'fractionnable' => true,
            'nb_tranches_max' => 2,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id' => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total' => 30000,
            'montant_paye' => 0,
            'statut' => 'impaye',
        ]);

        $paiement = Paiement::create([
            'user_id' => $user->id,
            'apprenant_id' => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant' => 10000,
            'frais_service' => 200,
            'montant_total_paye' => 10200,
            'frais_aangaraa' => 200,
            'marge_edupay' => 0,
            'mode_paiement' => 'mtn_momo',
            'type_paiement' => 'integral',
            'statut' => 'en_attente',
            'telephone_paiement' => '650000001',
            'pay_token' => 'WEBHOOK_TOKEN_123',
            'reference' => 'PAYREF123',
            'aangaraa_transaction_id' => 'PAYREF123',
            'operateur' => 'MTN_Cameroon',
            'date_paiement' => now(),
        ]);

        $mock = Mockery::mock(AangaraaPayService::class);
        $mock->shouldReceive('verifierStatut')->with('WEBHOOK_TOKEN_123')->andReturn([
            'statut' => 'SUCCESSFUL',
            'succes' => true,
            'message' => 'OK',
        ]);
        $mock->shouldReceive('reverserEtablissement')->andReturn([
            'succes' => true,
            'reference' => 'REVHOOK123',
        ]);

        $this->app->instance(AangaraaPayService::class, $mock);

        $payload = [
            'transaction_id' => 'PAYREF123',
            'status' => 'SUCCESSFUL',
            'amount' => 10200,
        ];

        $this->postJson('/webhook/aangaraapay', $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $paiement->refresh();
        $this->assertEquals('valide', $paiement->statut);

        $commission = Commission::where('paiement_id', $paiement->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals('prelevee', $commission->statut);
        $this->assertEquals('REVHOOK123', $commission->reference_reversement);
    }
}
