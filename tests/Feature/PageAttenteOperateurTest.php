<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La page d'attente ne doit pas donner la meme consigne a MTN et Orange.
 *
 * AangaraaPay declenche deux mecanismes DIFFerents selon l'operateur :
 *
 * - MTN : un prompt USSD. Le payeur doit composer *126# puis appuyer sur 1
 *   (le payToken est un UUID).
 * - Orange : une NOTIFICATION Orange Money. Le payeur saisit son code secret a
 *   4 chiffres, sans composer quoi que ce soit (le payToken commence par MP).
 *
 * La page affichait *126# et « Appuyez sur 1 » pour les deux, ce qui,
// sur un paiement Orange, envoyait le payeur composer #150*50# — un code
 * Orange valide mais qui correspond au paiement marchand PAR CODE, pas a la
 * validation d'une transaction deja initiatee par API. Compose, il obtenait
 * une erreur de l'operateur. Constate en production le 01/10/2026.
 *
 * La page affiche aussi le detail des frais : seul le total etait montre, donc
 * le payeur ne pouvait pas verifier que le debit de son compte correspondait.
 */
class PageAttenteOperateurTest extends TestCase
{
    use RefreshDatabase;

    private User $payeur;

    private FraisApprenant $frais;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'parent']);

        $etab = Etablissement::create([
            'code_etablissement' => 'ETABATT01',
            'nom'               => 'Ecole Attente',
            'type'              => 'lycee_general',
            'statut_juridique'  => 'prive_laic',
            'region'            => 'centre',
            'ville'             => 'Yaounde',
            'telephone'         => '650000000',
            'email'             => 'attente@test.cm',
            'taux_commission'   => 0.05,
            'statut'            => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id'         => $etab->id,
            'nom'                      => 'Eleve',
            'prenom'                   => 'Attente',
            'classe'                   => '1ere',
            'statut_paiement'          => 'impaye',
            'actif'                    => true,
            'valide_par_etablissement' => true,
        ]);

        $this->payeur = User::factory()->create();
        $this->payeur->assignRole('parent');
        $this->payeur->apprenants()->attach($apprenant->id, ['lien' => 'parent']);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etab->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'fractionnable'    => true,
            'nb_tranches_max'  => 1,
            'annee_scolaire'   => '2026-2027',
            'actif'            => true,
        ]);

        $this->frais = FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'     => 50000,
            'montant_paye'      => 0,
            'statut'            => 'impaye',
            'annee_scolaire'    => '2026-2027',
        ]);
    }

    private function creerPaiement(string $operateur, int $montant = 50000, int $frais = 1150): Paiement
    {
        return Paiement::create([
            'user_id'            => $this->payeur->id,
            'apprenant_id'       => $this->frais->apprenant_id,
            'frais_apprenant_id' => $this->frais->id,
            'montant'            => $montant,
            'frais_service'      => $frais,
            'montant_total_paye' => $montant + $frais,
            'mode_paiement'      => $operateur === 'Orange_Cameroon' ? 'orange_money' : 'mtn_momo',
            'type_paiement'      => 'integral',
            'statut'             => 'en_attente',
            'operateur'          => $operateur,
            'telephone_paiement' => '237691234567',
            'pay_token'          => $operateur === 'Orange_Cameroon' ? 'MP2512295AD67EBB09E121E235B2' : 'e92224da-1987-47e6-958b-78433bd92a66',
            'date_paiement'      => now(),
        ]);
    }

    public function test_mtn_affiche_le_parcours_ussd_avec_le_code_126(): void
    {
        $paiement = $this->creerPaiement('MTN_Cameroon');

        $vue = $this->actingAs($this->payeur)
            ->get(route('payeur.paiement.attente', $paiement))
            ->assertOk()
            ->getContent();

        // Le parcours USSD est visible et actif.
        $this->assertStringContainsString('msg-attente-detail-mtn', $vue);
        $this->assertStringContainsString('*126#', $vue);

        // ... et le parcours Orange est masque.
        $this->assertMatchesRegularExpression(
            '/id="msg-attente-detail-orange"[^>]*display:none;/',
            $vue,
            'Le bloc Orange doit etre masque pour un paiement MTN.'
        );
    }

    public function test_orange_affiche_le_parcours_notification_sans_code_ussd(): void
    {
        $paiement = $this->creerPaiement('Orange_Cameroon');

        $vue = $this->actingAs($this->payeur)
            ->get(route('payeur.paiement.attente', $paiement))
            ->assertOk()
            ->getContent();

        // Le parcours notification est visible, donc pas masque.
        $this->assertDoesNotMatchRegularExpression(
            '/id="msg-attente-detail-orange"[^>]*display:none;/',
            $vue,
            'Le bloc Orange doit etre visible pour un paiement Orange.'
        );

        // Le bloc MTN est masque : aucun *126# visible pour un payeur Orange.
        $this->assertMatchesRegularExpression(
            '/id="msg-attente-detail-mtn"[^>]*display:none;/',
            $vue,
            'Le bloc MTN doit etre masque pour un paiement Orange.'
        );

        // Le nom de l'operateur affiche est bien Orange.
        $this->assertStringContainsString('Orange Money', $vue);
    }

    public function test_le_parcours_orange_mentionne_le_code_secret_et_interdit_le_code_ussd(): void
    {
        $paiement = $this->creerPaiement('Orange_Cameroon');

        $vue = $this->actingAs($this->payeur)
            ->get(route('payeur.paiement.attente', $paiement))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('code secret Orange Money', $vue);
        $this->assertStringContainsString('Ne composez aucun code USSD', $vue);
    }

    public function test_le_detail_des_frais_est_affiche_avec_le_total_debite(): void
    {
        $paiement = $this->creerPaiement('Orange_Cameroon', 50000, 1150);

        $vue = $this->actingAs($this->payeur)
            ->get(route('payeur.paiement.attente', $paiement))
            ->assertOk()
            ->getContent();

        // Les trois lignes du recap : frais scolaires, frais de service, total.
        $this->assertStringContainsString('Frais scolaires', $vue);
        $this->assertStringContainsString('Frais de service', $vue);
        $this->assertStringContainsString('Total d', $vue);

        // Les montants : 50 000 + 1 150 = 51 150 FCFA. number_format utilise
        // une espace insecable, d'ou la tolerance sur le separateur.
        $this->assertMatchesRegularExpression('/50[\s&;a-z0-9]*000 FCFA/', $vue);
        $this->assertMatchesRegularExpression('/1[\s&;a-z0-9]*150 FCFA/', $vue);
        $this->assertMatchesRegularExpression('/51[\s&;a-z0-9]*150 FCFA/', $vue);
    }

    public function test_un_paiement_sans_operateur_ne_casse_pas_la_page(): void
    {
        // operateur est nullable (paiement cree par l'API mobile, ou initie
        // avant le retour de l'API) : la page doit rester lisible.
        $paiement = $this->creerPaiement('MTN_Cameroon');
        $paiement->update(['operateur' => null]);

        $vue = $this->actingAs($this->payeur)
            ->get(route('payeur.paiement.attente', $paiement))
            ->assertOk()
            ->getContent();

        // Sans operateur connu on retombe sur le parcours USSD, qui est le
        // defaut historique : mieux vaut ca qu'un ecran vide.
        $this->assertDoesNotMatchRegularExpression(
            '/id="msg-attente-detail-mtn"[^>]*display:none;/',
            $vue
        );
    }
}
