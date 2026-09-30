<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\User;
use App\Support\AnneeScolaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'eleve ne doit voir que les frais de l'annee scolaire active.
 *
 * Le back-office filtrait ses indicateurs sur `AnneeScolaire::active()` depuis
 * le depart. Le cote payeur, lui, additionnait `$apprenant->frais` sans aucun
 * filtre : un frais rattache a une annee close restait affiche comme du et
 * payable, alors que le back-office ne le voyait plus. Les deux ecrans
 * divergeaient sur le meme dossier.
 *
 * Consequence financiere : le bouton « Payer maintenant » pointait sur
 * `$premierFraisImpaye`, calcule sans filtre — un eleve pouvait declencher un
 * paiement sur une annee cloturee.
 */
class AnneeScolaireFraisPayeurTest extends TestCase
{
    use RefreshDatabase;

    private function etablissement(): Etablissement
    {
        return Etablissement::create([
            'code_etablissement' => 'TEST-2026',
            'nom'                 => 'Établissement de test',
            'type'                => 'universite',
            'statut_juridique'    => 'public',
            'region'              => 'littoral',
            'statut'              => 'actif',
            'ville'               => 'Douala',
            'telephone'           => '699401234',
            'email'               => 'contact@test.cm',
        ]);
    }

    private function apprenant(Etablissement $etablissement): Apprenant
    {
        return Apprenant::create([
            'etablissement_id' => $etablissement->id,
            'nom'              => 'FONO',
            'prenom'           => 'Carine',
            'classe'           => 'Master 1 Informatique',
            'actif'            => true,
        ]);
    }

    private function categorie(Etablissement $etablissement, string $nom = 'Scolarité'): CategoriesFrais
    {
        return CategoriesFrais::create([
            'etablissement_id' => $etablissement->id,
            'nom'              => $nom,
            'montant_total'    => 100000,
        ]);
    }

    private function frais(Apprenant $apprenant, string $annee, float $total, float $paye): FraisApprenant
    {
        return FraisApprenant::create([
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => $this->categorie($apprenant->etablissement)->id,
            'montant_total'  => $total,
            'montant_paye'   => $paye,
            'statut'         => $paye >= $total ? 'regle' : 'impaye',
            'annee_scolaire' => $annee,
        ]);
    }

    public function test_frais_an_annee_active_seul_sont_conserves(): void
    {
        $etablissement = $this->etablissement();
        $apprenant     = $this->apprenant($etablissement);
        $active        = AnneeScolaire::active($etablissement);

        $this->frais($apprenant, $active, 100000, 40000);
        $this->frais($apprenant, '2019-2020', 50000, 0);

        $frais = $apprenant->fraisAnneeActive();

        $this->assertCount(1, $frais, 'Seul un frais existe sur l\'année active');
        $this->assertSame($active, $frais->first()->annee_scolaire);
        $this->assertSame(60000.0, (float) $frais->sum(fn ($f) => $f->montant_total - $f->montant_paye));
    }

    /**
     * C'est le cas rencontre en production : un dossier unique rattache a une
     * annee close. Le back-office n'affichait rien, l'eleve voyait un solde.
     */
    public function test_un_dossier_entierement_annee_close_napparait_plus(): void
    {
        $etablissement = $this->etablissement();
        $apprenant     = $this->apprenant($etablissement);

        $this->frais($apprenant, '2019-2020', 95000, 60000);

        $this->assertCount(0, $apprenant->fraisAnneeActive());
        $this->assertSame(0, (int) $apprenant->fraisAnneeActive()->sum(fn ($f) => $f->montant_total - $f->montant_paye));
    }

    public function test_les_frais_de_toutes_les_annees_restent_en_base(): void
    {
        $etablissement = $this->etablissement();
        $apprenant     = $this->apprenant($etablissement);
        $active        = AnneeScolaire::active($etablissement);

        $this->frais($apprenant, $active, 10000, 0);
        $this->frais($apprenant, '2019-2020', 20000, 0);
        $this->frais($apprenant, '2020-2021', 30000, 30000);

        // Le filtre est une vue, pas une suppression : l'historique reste
        // consultable et les montants deja payes ne sont pas perdus.
        $this->assertCount(3, $apprenant->frais);
    }

    /**
     * Deux apprenants rattaches a la meme personne mais a des etablissements
     * distincts : chacun doit etre filtre sur SA propre annee active.
     */
    public function test_le_filtre_suit_l_annee_active_de_l_etablissement_de_l_apprenant(): void
    {
        Role::firstOrCreate(['name' => 'eleve', 'guard_name' => 'web']);

        $autre = $this->etablissement();
        $autre->update(['code_etablissement' => 'TEST-2027', 'annee_scolaire_active' => '2020-2021']);

        $premier  = $this->apprenant($this->etablissement());
        $second   = $this->apprenant($autre);

        $this->frais($premier, AnneeScolaire::active($premier->etablissement), 10000, 0);
        $this->frais($premier, '2020-2021', 99000, 0);
        $this->frais($second, '2020-2021', 50000, 0);

        $this->assertSame(10000.0, (float) $premier->fraisAnneeActive()->sum('montant_total'));
        $this->assertSame(50000.0, (float) $second->fraisAnneeActive()->sum('montant_total'));
    }

    public function test_un_apprenant_sans_frais_ne_renvoie_rien(): void
    {
        $apprenant = $this->apprenant($this->etablissement());

        $this->assertTrue($apprenant->fraisAnneeActive()->isEmpty());
        $this->assertSame(0, (int) $apprenant->fraisAnneeActive()->sum('montant_total'));
    }
}