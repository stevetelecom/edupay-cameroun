<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Abonnement;
use App\Models\CategoriesFrais;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Page « Reversements » du back-office établissement.
 *
 * Rend visible pour l'école l'argent qui lui revient réellement
 * (net des frais EduPay) : reversé, en cours, et surtout les sommes
 * bloquées (a_verifier / echec) qui exigent une action humaine.
 */
class ReversementsBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private Etablissement $autreEtab;
    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'directeur']);

        $this->etab = Etablissement::create([
            'code_etablissement' => 'ETAB-REV2',
            'nom'                => 'Ecole Reversements Back Office',
            'type'               => 'lycee_general',
            'statut_juridique'   => 'prive_laic',
            'region'             => 'centre',
            'ville'              => 'Yaounde',
            'telephone'          => '650000000',
            'email'              => 'admin@revbo.test',
            'taux_commission'    => 0.05,
            'statut'             => 'actif',
        ]);

        // Un AUTRE établissement : son directeur ne doit jamais voir nos commissions.
        $this->autreEtab = Etablissement::create([
            'code_etablissement' => 'ETAB-REV3',
            'nom'                => 'Autre Ecole',
            'type'               => 'ecole_primaire',
            'statut_juridique'   => 'prive_laic',
            'region'             => 'centre',
            'ville'              => 'Douala',
            'telephone'          => '670000000',
            'email'              => 'admin@autre.test',
            'taux_commission'    => 0.05,
            'statut'             => 'actif',
        ]);

        $this->directeur = User::factory()->create([
            'etablissement_id' => $this->etab->id,
        ]);
        $this->directeur->assignRole('directeur');

        // Un abonnement actif : le middleware CheckAbonnement redirige toute
        // page du back-office vers etablissement.abonnement.requis quand
        // l'établissement n'a pas d'abonnement valide.
        Abonnement::create([
            'etablissement_id' => $this->etab->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => Carbon::today()->subMonth(),
            'date_fin'         => Carbon::today()->addMonth(),
            'grace_period_fin' => Carbon::today()->addMonth()->addDays(7),
            'statut'           => 'actif',
        ]);

        $this->actingAs($this->directeur);
    }

    private function creerCommission(int $montantNet, string $statut, ?Etablissement $etab = null): Commission
    {
        $etablissement = $etab ?? $this->etab;

        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id,
            'nom'              => 'Eleve',
            'prenom'           => 'Test',
            'classe'           => '1ere',
            'statut_paiement'  => 'impaye',
            'actif'            => true,
        ]);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etablissement->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'fractionnable'    => false,
            'nb_tranches_max'  => 1,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'      => 50000,
            'montant_paye'       => 0,
            'statut'             => 'impaye',
        ]);

        $paiement = Paiement::create([
            'user_id'            => User::factory()->create()->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => 50000,
            'frais_service'      => 800,
            'montant_total_paye' => 50800,
            'mode_paiement'      => 'mtn_momo',
            'type_paiement'      => 'integral',
            'statut'             => 'valide',
            'reference'          => 'EP-REV-TEST-' . random_int(1000, 9999),
            'telephone_paiement' => '650123456',
            'date_paiement'      => now(),
        ]);

        $commission = Commission::create([
            'paiement_id'               => $paiement->id,
            'etablissement_id'          => $etablissement->id,
            'montant_transaction'       => 50000,
            'taux'                      => 0.05,
            'montant_commission'        => 2500,
            'montant_net_etablissement' => $montantNet,
            'frais_aangaraa'            => 800,
            'statut'                    => $statut,
        ]);

        if ($statut === Commission::STATUT_PRELEVEE) {
            $commission->update([
                'reference_reversement' => 'AANG-REF-' . $commission->id,
                'reversed_at'           => now(),
            ]);
        }

        return $commission;
    }

    public function test_page_liste_reversements_de_son_etablissement(): void
    {
        $reverse      = $this->creerCommission(45000, Commission::STATUT_PRELEVEE);
        $aVerifier    = $this->creerCommission(12000, Commission::STATUT_A_VERIFIER);
        $echec        = $this->creerCommission(3000, Commission::STATUT_ECHEC);
        $enCours      = $this->creerCommission(20000, Commission::STATUT_EN_COURS);
        $calculee     = $this->creerCommission(9000, Commission::STATUT_CALCULEE);
        $autreEcole   = $this->creerCommission(99999, Commission::STATUT_PRELEVEE, $this->autreEtab);

        $reponse = $this->get(route('etablissement.reversements.index'))
            ->assertStatus(200)
            ->assertSee('Reversements');

        // Les 5 commissions de SON école sont visibles via leur référence paiement.
        foreach ([$reverse, $aVerifier, $echec, $enCours, $calculee] as $commission) {
            $this->assertStringContainsString($commission->paiement->reference, $reponse->getContent());
            $this->assertStringContainsString(
                number_format((int) $commission->montant_net_etablissement, 0, ',', ' '),
                $reponse->getContent()
            );
        }

        // La commission de l'autre école n'apparaît nulle part.
        $this->assertStringNotContainsString($autreEcole->paiement->reference, $reponse->getContent());
    }

    public function test_totaux_kpis_resumes_par_statut(): void
    {
        $this->creerCommission(45000, Commission::STATUT_PRELEVEE);
        $this->creerCommission(5000,  Commission::STATUT_PRELEVEE);
        $this->creerCommission(12000, Commission::STATUT_A_VERIFIER);
        $this->creerCommission(3000,  Commission::STATUT_ECHEC);
        $this->creerCommission(9000,  Commission::STATUT_CALCULEE);

        $reponse = $this->get(route('etablissement.reversements.index'))
            ->assertStatus(200);

        // Reversé : 45000 + 5000 = 50000
        $this->assertStringContainsString('50 000', $reponse->getContent());
        // À traiter : 12000 + 3000 = 15000
        $this->assertStringContainsString('15 000', $reponse->getContent());
        // Bandeau d'alerte : 2 opérations bloquées
        $this->assertStringContainsString('2', $reponse->getContent());
    }

    public function test_filtre_par_statut(): void
    {
        $reverse = $this->creerCommission(45000, Commission::STATUT_PRELEVEE);
        $echec   = $this->creerCommission(3000, Commission::STATUT_ECHEC);

        $reponse = $this->get(route('etablissement.reversements.index', ['statut' => 'echec']))
            ->assertStatus(200);

        $this->assertStringContainsString($echec->paiement->reference, $reponse->getContent());
        $this->assertStringNotContainsString($reverse->paiement->reference, $reponse->getContent());
    }

    public function test_filtre_par_recherche_reference(): void
    {
        $commission = $this->creerCommission(45000, Commission::STATUT_PRELEVEE);

        $reponse = $this->get(route('etablissement.reversements.index', [
            'q' => $commission->paiement->reference,
        ]))->assertStatus(200);

        $this->assertStringContainsString($commission->paiement->reference, $reponse->getContent());
    }

    public function test_sans_authentification_redirige_vers_connexion(): void
    {
        \Illuminate\Support\Facades\Auth::logout();

        // Ni le middleware auth ni le rôle ne sont exécutés : l'utilisateur
        // est simplement renvoyé vers la page de connexion.
        $this->get(route('etablissement.reversements.index'))
            ->assertRedirect(route('login'));
    }
}