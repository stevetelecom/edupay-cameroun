<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'annuaire public de la landing page chargeait TOUS les établissements
 * actifs d'un coup, puis le filtre JavaScript n'affichait que les 12 premières
 * cartes : au-delà de 12 écoles, toute école au-delà de la 12e restait
 * introuvable, et le compteur de résultats était faux.
 */
class AnnuairePublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // le middleware de maintenance lit parametres_systeme sur chaque requête
        \App\Models\ParametreSysteme::create(['cle' => 'maintenance', 'valeur' => '0']);
    }

    private function creerEtablissement(string $nom, string $ville = 'Yaounde', string $type = 'lycee_general', string $statut = 'actif'): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => 'ETB-'.strtoupper(substr(md5($nom), 0, 8)),
            'nom'                   => $nom,
            'type'                  => $type,
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => $ville,
            'telephone'             => '650'.random_int(100000, 999999),
            'email'                 => strtolower(str_replace(' ', '', $nom)).'@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => $statut,
            'annee_scolaire_active' => '2026-2027',
        ]);
    }

    public function test_l_annuaire_public_est_pagene_mais_pas_au_dela_d_un_annuaire_courant()
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->creerEtablissement(sprintf('Ecole %02d', $i));
        }

        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertViewHas('etablissements', function ($paginator) {
            // Le plafond de 12/page cachait la 14e école (ex. Université de
            // Douala) hors de la page d'accueil. Un annuaire courant (15
            // écoles) doit tenir sur une seule page.
            return $paginator->count() === 15 && $paginator->total() === 15;
        });
    }

    public function test_la_pagination_propose_la_page_suivante_avec_le_filtre()
    {
        // Au-dela du plafond (60/page) la pagination doit reapparaitre.
        for ($i = 1; $i <= 65; $i++) {
            $this->creerEtablissement(sprintf('Ecole %02d', $i));
        }

        $reponse = $this->get('/?type=lycee_general');

        $reponse->assertOk();
        $reponse->assertSee('Ecole 01', false);
        $reponse->assertDontSee('Ecole 65', false);
        $reponse->assertSee('page=2', false);
    }

    public function test_une_recherche_trouve_une_ecole_au_dela_de_la_premiere_page()
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->creerEtablissement(sprintf('Ecole %02d', $i));
        }
        // tri par nom : « Ecole Zenith » arrive donc en 15e position
        $cible = $this->creerEtablissement('Ecole Zenith', 'Kribi');

        $reponse = $this->get('/?q=Zenith');

        $reponse->assertOk();
        $reponse->assertViewHas('q', 'Zenith');
        $reponse->assertSee('Ecole Zenith', false);
        $reponse->assertViewHas('etablissements', fn ($p) => $p->total() === 1);
    }

    public function test_la_recherche_porte_sur_le_nom_la_ville_et_le_code()
    {
        $this->creerEtablissement('Ecole Danga', 'Kribi');
        $this->creerEtablissement('Ecole Express', 'Douala');

        $this->get('/?q=Kribi')->assertSee('Ecole Danga', false);
        $this->get('/?q=Express')->assertSee('Ecole Express', false);

        $parCode = $this->creerEtablissement('Ecole Code');
        $this->get('/?q='.$parCode->code_etablissement)->assertSee('Ecole Code', false);
    }

    public function test_le_filtre_par_type_est_applique()
    {
        $this->creerEtablissement('Ecole Primaire', 'Yaounde', 'primaire');
        $this->creerEtablissement('Ecole College', 'Yaounde', 'college');

        $reponse = $this->get('/?type=primaire');

        $reponse->assertOk();
        $reponse->assertSee('Ecole Primaire', false);
        $reponse->assertDontSee('Ecole College', false);
    }

    public function test_un_type_inconnu_ne_renvoie_pas_tout_le_monde()
    {
        $this->creerEtablissement('Ecole Primaire', 'Yaounde', 'primaire');

        $reponse = $this->get('/?type=inexistant');

        $reponse->assertOk();
        $reponse->assertViewHas('type', '');
        $reponse->assertViewHas('etablissements', fn ($p) => $p->total() === 1);
    }

    public function test_les_etablissements_inactifs_ne_sont_pas_annonces()
    {
        $this->creerEtablissement('Ecole Visible');
        $this->creerEtablissement('Ecole Masquee', 'Yaounde', 'lycee_general', 'inactif');

        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertSee('Ecole Visible', false);
        $reponse->assertDontSee('Ecole Masquee', false);
    }

    public function test_le_logo_est_charge_pour_chaque_carte()
    {
        $etab = $this->creerEtablissement('Ecole Logo');
        $etab->update(['logo' => 'etablissements/logo-test.png']);

        $reponse = $this->get('/');

        $reponse->assertOk();
        // la vue affiche $etab->logo : si la colonne n'est pas	select()ee, le
        // logo disparait silencieusement de toutes les cartes
        $reponse->assertViewHas('etablissements', fn ($p) => $p->first()->logo === 'etablissements/logo-test.png');
        $reponse->assertSee('etablissements/logo-test.png', false);
    }

    public function test_une_recherche_sans_resultat_ne_crashe_pas()
    {
        $this->creerEtablissement('Ecole Visible');

        $reponse = $this->get('/?q=zzzzz-rien');

        $reponse->assertOk();
        $reponse->assertViewHas('etablissements', fn ($p) => $p->total() === 0);
        // un filtre sans resultat ne doit pas afficher le CTA « sois notre partenaire »
        $reponse->assertSee(__('public.aucun_etab_trouve'), false);
        $reponse->assertDontSee(__('public.aucun_etab_partenaire'), false);
    }

    public function test_le_caractere_joker_dune_recherche_ne_crashe_pas()
    {
        $this->creerEtablissement('Ecole Visible');

        // un LIKE non echappe interpretait % et _ comme des jokers SQL
        $this->get('/?q=%25')->assertOk();
        $this->get('/?q=_')->assertOk();
    }

    public function test_le_chaque_public_ne_charge_pas_les_donnees_de_tous_les_paiements()
    {
        $etablissement = $this->creerEtablissement('Ecole Compteurs');
        $apprenant = Apprenant::create([
            'etablissement_id'         => $etablissement->id,
            'matricule'                => 'CNT-1',
            'nom'                      => 'Test',
            'prenom'                   => 'Un',
            'classe'                   => '6eme',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
        $frais = \App\Models\FraisApprenant::create([
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => \App\Models\CategoriesFrais::create([
                'etablissement_id' => $etablissement->id,
                'nom'              => 'Scolarite',
                'montant_total'    => 10000,
                'actif'            => true,
                'annee_scolaire'   => '2026-2027',
            ])->id,
            'annee_scolaire'     => '2026-2027',
            'montant_total'      => 10000,
            'montant_paye'       => 10000,
            'statut'             => 'regle',
        ]);
        Paiement::create([
            'reference'          => 'CNT-P1',
            'user_id'            => User::factory()->create()->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => 10000,
            'mode_paiement'      => 'mobile_money',
            'statut'             => 'valide',
            'date_paiement'      => '2026-09-01 10:00:00',
        ]);

        $reponse = $this->get('/');

        $reponse->assertOk();
        $reponse->assertViewHas('stats', fn ($stats) => $stats['nb_etablissements'] === 1
            && $stats['nb_apprenants'] === 1
            && $stats['nb_paiements'] === 1);
    }
}
