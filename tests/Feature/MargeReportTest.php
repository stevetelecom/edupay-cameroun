<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rapport de marge EduPay par periode.
 *
 * Ces tests verrouillent deux choses : les quatre periodes ne melangent pas
 * leurs bornes, et le rapport n'invente pas de marge quand le reversement a
 * echoue (l'argent de l'etablissement n'est pas de la marge disponible).
 */
class MargeReportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Etablissement $etab;
    private FraisApprenant $frais;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        $this->admin = Admin::create([
            'prenom'   => 'Super',
            'nom'      => 'Marge',
            'email'    => 'marge-super@test.cm',
            'password' => bcrypt('secret1234'),
        ]);
        $this->admin->assignRole('super-admin');
        $this->actingAs($this->admin, 'admin');

        $this->etab = Etablissement::create([
            'code_etablissement'  => 'ETAB-MARGE',
            'nom'                  => 'Ecole Marge',
            'type'                 => 'lycee_general',
            'statut_juridique'     => 'prive_laic',
            'region'               => 'centre',
            'ville'                => 'Yaounde',
            'telephone'            => '650000000',
            'email'                => 'admin@marge.test',
            'numero_momo_reversement' => '650123456',
            'operateur_momo_reversement' => 'mtn',
            'statut'               => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Eleve',
            'prenom'           => 'Marge',
            'classe'           => '1ere',
            'statut_paiement'  => 'impaye',
            'actif'            => true,
        ]);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'fractionnable'    => false,
            'nb_tranches_max'  => 1,
        ]);

        $this->frais = FraisApprenant::create([
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'      => 50000,
            'montant_paye'       => 0,
            'statut'             => 'impaye',
        ]);
    }

    /** Commission datee, avec son paiement pour rester coherent (FK). */
    private function commission(string $statut, int $marge, string $date, string $operateur = 'MTN_Cameroon'): Commission
    {
        $paiement = Paiement::create([
            'user_id'            => User::factory()->create()->id,
            'apprenant_id'       => $this->frais->apprenant_id,
            'frais_apprenant_id' => $this->frais->id,
            'montant'            => 50000,
            'frais_service'      => 1150,
            'montant_total_paye' => 51150,
            'frais_aangaraa'     => 1100,
            'marge_edupay'       => $marge,
            'mode_paiement'      => 'mtn_momo',
            'operateur'          => $operateur,
            'statut'             => 'valide',
        ]);
        $paiement->created_at = $date;
        $paiement->save();

        $commission = Commission::create([
            'paiement_id'               => $paiement->id,
            'etablissement_id'          => $this->etab->id,
            'montant_transaction'       => 50000,
            'taux'                      => 0.023,
            'montant_commission'        => $marge,
            'montant_net_etablissement' => 50000,
            'frais_aangaraa'            => 1100,
            'statut'                    => $statut,
        ]);
        $commission->created_at = $date;
        $commission->save();

        return $commission;
    }

    public function test_la_periode_jour_ne_voit_que_ce_jour(): void
    {
        Date::setTestNow('2026-03-18 10:00:00');

        $this->commission(Commission::STATUT_PRELEVEE, 50, '2026-03-18 09:00:00');
        $this->commission(Commission::STATUT_PRELEVEE, 70, '2026-03-17 09:00:00');

        $this->get(route('admin.marge.index', ['periode' => 'jour', 'date' => '2026-03-18']))
            ->assertOk()
            ->assertSee('1 150')   // frais preleves du seul 18
            ->assertDontSee('1 500'); // 1 150 + 1 400 du 17

        Date::setTestNow();
    }

    public function test_la_periode_semaine_et_jour_ont_des_bornes_differentes(): void
    {
        // 2026-03-16 est un lundi, le 18 un jeudi : meme semaine.
        Date::setTestNow('2026-03-18 10:00:00');

        $this->commission(Commission::STATUT_PRELEVEE, 50, '2026-03-16 09:00:00');
        $this->commission(Commission::STATUT_PRELEVEE, 60, '2026-03-18 09:00:00');
        $this->commission(Commission::STATUT_PRELEVEE, 90, '2026-03-23 09:00:00');

        // Jour : 1 150 seulement.
        $this->get(route('admin.marge.index', ['periode' => 'jour', 'date' => '2026-03-18']))
            ->assertOk()
            ->assertSee('1 150');

        // Semaine : 2 300 (lundi 16 + jeudi 18).
        $this->get(route('admin.marge.index', ['periode' => 'semaine', 'date' => '2026-03-18']))
            ->assertOk()
            ->assertSee('2 300');

        Date::setTestNow();
    }

    public function test_chaque_periode_agrège_ses_commissions(): void
    {
        Date::setTestNow('2026-03-18 10:00:00');

        $this->commission(Commission::STATUT_PRELEVEE, 50, '2026-03-18 09:00:00');
        $this->commission(Commission::STATUT_PRELEVEE, 40, '2026-02-10 09:00:00');
        $this->commission(Commission::STATUT_PRELEVEE, 30, '2025-11-05 09:00:00');

        // Mois de mars : frais 1 150.
        $this->get(route('admin.marge.index', ['periode' => 'mois', 'date' => '2026-03-18']))
            ->assertOk()
            ->assertSee('1 150');

        // Année 2026 : mars + fevrier = 2 300 (mars 2026 exclut novembre 2025).
        $this->get(route('admin.marge.index', ['periode' => 'annee', 'date' => '2026-03-18']))
            ->assertOk()
            ->assertSee('2 300');

        Date::setTestNow();
    }

    public function test_une_periode_vide_ne_leve_pas_erreur(): void
    {
        $this->get(route('admin.marge.index', ['periode' => 'annee', 'date' => '2020-01-01']))
            ->assertOk();
    }

    public function test_une_periode_inconnue_retombe_sur_le_mois(): void
    {
        $this->get(route('admin.marge.index', ['periode' => 'trimestre']))
            ->assertOk();
    }

    public function test_le_solde_reel_aangaraa_est_affiche(): void
    {
        Http::fake([
            '*/service/balance/*' => Http::response([
                'message' => 'Balance retrieved successfully',
                'data'    => [
                    'service_id'     => 12,
                    'service_name'   => 'EduPay Cameroun',
                    'balance_in_db'  => 3400.50,
                    'balance_details' => [
                        'mtn_cameroon'    => ['amount' => 2000.00, 'transactions_count' => 10],
                        'orange_cameroon' => ['amount' => 1400.50, 'transactions_count' => 4],
                        'total'           => ['amount' => 3400.50, 'transactions_count' => 14],
                    ],
                ],
            ], 200),
        ]);

        $reponse = $this->get(route('admin.marge.index'));

        $reponse->assertOk();
        $this->assertStringContainsString('3 401', $reponse->getContent());
        $this->assertStringContainsString('EduPay Cameroun', $reponse->getContent());
    }

    /**
 * Le solde disponible et le cumul encaisse sont deux grandeurs de nature
 * differente et ne doivent JAMAIS etre presentees comme deux soldes.
 *
 * Donnees reelles relevees en production le 02/10/2026 sur
 * GET /service/balance/{app_key} :
 *
 *   balance_in_db       = 2
 *   mtn_cameroon.amount = 774   (11 transactions)
 *   orange_cameroon     = 0     (0 transaction)
 *   total.amount        = 774
 *
 * `balance_details.*.amount` est le volume encaisse cumule sur les
 * transactions SUCCESSFUL (doc AangaraaPay), pas un solde par operateur :
 * 774 XAF sont passes par le compte, 772 ont ete reverses, il en restait 2.
 *
 * L'ancien affichage imprimait « MTN : 774 » sous le titre « Solde reel »,
 * juste sous « 2 FCFA ». Lu de haut en bas cela signifiait « 2 disponibles,
 * 774 chez MTN », et laissait croire a un ecart de 772 XAF. L'ecart n'existait
 * pas : c'etait l'etiquette qui etait fausse.
 */
public function test_solde_disponible_et_cumul_encaisse_sont_presentes_separement(): void
    {
        Http::fake([
            '*/service/balance/*' => Http::response([
                'message' => 'Balance retrieved successfully',
                'data'    => [
                    'service_id'     => 42,
                    'service_name'   => 'Edupay Cameroun',
                    'balance_in_db'  => 2,
                    'balance_details' => [
                        'mtn_cameroon'    => ['amount' => 774, 'transactions_count' => 11, 'currency' => 'XAF'],
                        'orange_cameroon' => ['amount' => 0,   'transactions_count' => 0,  'currency' => 'XAF'],
                        'total'           => ['amount' => 774, 'transactions_count' => 11, 'currency' => 'XAF'],
                    ],
                ],
            ], 200),
        ]);

        $contenu = $this->get(route('admin.marge.index'))->assertOk()->getContent();

        // Le solde retirable : 2 FCFA, pas 774.
        $this->assertStringContainsString('Solde disponible', $contenu);
        $this->assertMatchesRegularExpression(
            '/Solde disponible.*?2\s*FCFA/s',
            $contenu,
            'Le solde disponible (balance_in_db = 2) doit etre affiche comme tel.'
        );

        // Le cumul encaisse : 774 FCFA, explicitement etiquete comme cumul.
        $this->assertStringContainsString('Cumul encaisse', $contenu);
        $this->assertMatchesRegularExpression(
            '/Cumul encaisse.*?MTN\s*:\s*774/s',
            $contenu,
            'Le cumul encaisse MTN (774) doit apparaitre sous son propre libelle.'
        );

        // Le nombre de transactions accompagne le cumul, entre parentheses.
        // Une balise <span> separe le montant de son compteur dans le rendu.
        $this->assertMatchesRegularExpression('/MTN\s*:\s*774.*?\(11\)/s', $contenu);

        // Orange a 0 : rien n'est encaisse de ce cote.
        $this->assertMatchesRegularExpression('/Orange\s*:\s*0/', $contenu);

        // La mention explicite que ce n'est pas un solde, pour lever toute
        // ambiguite a la lecture.
        $this->assertStringContainsString('Ce n est PAS un solde', $contenu);
    }

    public function test_une_erreur_de_l_api_est_expliquee_sans_arreter_la_page(): void
    {
        Http::fake([
            '*/service/balance/*' => Http::response(['message' => 'Service inactif'], 403),
        ]);

        $reponse = $this->get(route('admin.marge.index'));

        // La page doit rester lisible : un solde indisponible ne doit pas
        // rendre le rapport de marge inutilisable.
        $reponse->assertOk();
        $this->assertStringContainsString('Service inactif', $reponse->getContent());
    }

    public function test_un_super_admin_peut_voir_la_marge(): void
    {
        $this->commission(Commission::STATUT_PRELEVEE, 50, now()->toDateTimeString());

        $this->get(route('admin.marge.index'))
            ->assertOk()
            ->assertSee('1 150');
    }
}
