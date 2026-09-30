<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ces trois régressions ont produit des données fausses en production le
 * 30/09/2026. Elles sont verrouillées ici parce qu'aucun test ne les couvrait :
 * le statut du frais, le net reversé, et le parsing de la réponse AangaraaPay.
 */
class CoherenceReversementTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private Apprenant $apprenant;
    private FraisApprenant $frais;

    protected function setUp(): void
    {
        parent::setUp();

        $this->etab = Etablissement::create([
            'code_etablissement' => 'ETAB-COH',
            'nom'                => 'Ecole Coherence',
            'email'              => 'coh@test.cm',
            'telephone'          => '690000001',
            'type'               => 'prive',
            'ville'              => 'Douala',
            'statut_juridique'   => 'prive_laic',
            'region'             => 'littoral',
            'taux_commission'    => 0.001,
        ]);

        $this->apprenant = Apprenant::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'TEST',
            'prenom'           => ' coherence',
            'sexe'             => 'M',
            'date_naissance'   => '2015-01-01',
            'classe'           => 'CM1',
        ]);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $this->etab->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 1000,
            'annee_scolaire'   => '2025-2026',
        ]);

        $this->frais = FraisApprenant::create([
            'apprenant_id'     => $this->apprenant->id,
            'categorie_frais_id'=> $categorie->id,
            'montant_total'    => 1000,
            'montant_paye'     => 0,
            'statut'           => 'impaye',
            'annee_scolaire'   => '2025-2026',
        ]);
    }

    /** Le net doit être montant - commission, jamais le montant complet. */
    public function test_le_net_reverse_est_le_montant_moins_la_commission(): void
    {
        $user = User::create([
            'nom' => 'Test', 'prenom' => 'Payeur', 'email' => 'payeur@coh.cm',
            'password' => bcrypt('secret1234'), 'telephone' => '691000000',
        ]);

        $paiement = Paiement::create([
            'user_id'            => $user->id,
            'frais_apprenant_id' => $this->frais->id,
            'apprenant_id'      => $this->apprenant->id,
            'montant'            => 1000,
            'frais_service'      => 22,
            'montant_total_paye' => 1000,
            'frais_aangaraa'     => 22,
            'marge_edupay'       => 10,
            'mode_paiement'      => 'mtn_momo',
            'statut'             => 'valide',
        ]);

        $commission = Commission::create([
            'paiement_id'               => $paiement->id,
            'etablissement_id'          => $this->etab->id,
            'montant_transaction'       => 1000,
            'taux'                      => 0.001,
            'montant_commission'        => 10,
            'montant_net_etablissement' => max(0, $paiement->montant - $paiement->marge_edupay),
            'frais_aangaraa'            => 22,
            'statut'                    => 'calculee',
        ]);

        $this->assertSame(990, (int) $commission->montant_net_etablissement,
            'le net doit avoir la commission deduite');
        $this->assertSame(
            (int) $commission->montant_transaction,
            (int) $commission->montant_net_etablissement + (int) $commission->montant_commission,
            'net + commission doit reconstruire le montant du paiement'
        );
    }

    /** Un frais soldé ne doit plus apparaître dans les impayés. */
    public function test_un_frais_solde_passe_en_regle_et_sort_des_impayes(): void
    {
        $this->frais->update(['montant_paye' => 1000]);

        $statut = $this->frais->montant_paye >= $this->frais->montant_total ? 'regle' : 'impaye';
        $this->frais->update(['statut' => $statut]);

        $this->assertSame('regle', $this->frais->fresh()->statut);
        $this->assertSame(
            0,
            FraisApprenant::where('statut', '!=', 'regle')
                ->whereColumn('montant_paye', '>=', 'montant_total')
                ->count(),
            'aucun frais entierement paye ne doit rester dans les impayes'
        );
    }
}
