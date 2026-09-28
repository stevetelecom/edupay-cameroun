<?php

namespace Tests\Feature;

use App\Models\Abonnement;
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
 * Audit I / J / K — multi-sites (API etablissement).
 *
 * I : la liste des sites doit etre lisible a plat par le mobile, et un site
 *     portant des donnees ne peut pas etre supprime.
 * J : le verrou multi-sites doit porter sur l'abonnement du SITE PRINCIPAL
 *     et s'appliquer a la lecture, a la creation, a la modification ET a la
 *     suppression. Un plan inconnu ou l'absence d'abonnement doivent refuser.
 * K : les KPI d'un site ne doivent pas cumuler les annees differentes.
 */
class ApiEtablissementMultiSitesTest extends TestCase
{
    use RefreshDatabase;

    private const ANNEE = '2026-2027';

    private Etablissement $principal;

    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'directeur']);

        $this->principal = $this->creerEtablissement('Groupe Anacarde', null);
        $this->directeur = $this->creerDirecteur($this->principal);
    }

    private function creerEtablissement(string $nom, ?Etablissement $parent): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => $nom,
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => strtolower(str_replace(' ', '', $nom)) . '@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => self::ANNEE,
            'parent_etablissement_id' => $parent?->id,
        ]);
    }

    private function creerDirecteur(Etablissement $etablissement): User
    {
        $user = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $user->assignRole('directeur');

        return $user;
    }

    private function abonner(Etablissement $etablissement, string $plan, string $statut = 'actif'): Abonnement
    {
        return Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => $plan,
            'statut'           => $statut,
            'montant_mensuel'  => Abonnement::PLANS[$plan]['montant'],
            'date_debut'       => now()->subMonth(),
            'date_fin'         => now()->addMonths(10),
            'grace_period_fin' => now()->addMonths(11),
        ]);
    }

    private function siteAvecDonnees(Etablissement $parent, string $nom): Etablissement
    {
        $site = $this->creerEtablissement($nom, $parent);

        $apprenant = Apprenant::create([
            'etablissement_id'         => $site->id,
            'nom'                      => 'Eleve',
            'prenom'                   => $nom,
            'classe'                   => 'CM2',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => CategoriesFrais::create([
                'etablissement_id' => $site->id,
                'nom'              => 'Scolarite',
                'montant_total'    => 50000,
                'annee_scolaire'   => self::ANNEE,
            ])->id,
            'montant_total'     => 50000,
            'montant_paye'      => 0,
            'statut'            => 'impaye',
            'annee_scolaire'    => self::ANNEE,
        ]);

        Paiement::create([
            'user_id'            => $this->directeur->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => 50000,
            'frais_service'      => 1000,
            'montant_total_paye' => 51000,
            'frais_aangaraa'     => 1000,
            'marge_edupay'       => 0,
            'mode_paiement'      => 'mtn_momo',
            'type_paiement'      => 'integral',
            'statut'             => 'valide',
            'telephone_paiement' => '650000000',
            'date_paiement'      => now(),
        ]);

        return $site;
    }

    // ─────────────────────────────────────────────
    // J — verrou multi-sites
    // ─────────────────────────────────────────────

    public function test_sans_abonnement_le_multi_sites_est_refuse()
    {
        // 402 et non 403 : depuis que `check.abonnement` s'applique aussi a
        // l'API (parite avec routes/web.php:140), l'absence d'abonnement est
        // detectee avant le controle du plan, et repond 402 + un code dedie.
        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.sites.index'))
            ->assertStatus(402);

        $this->assertSame('abonnement_requis', $reponse->json('code'));
        $this->assertStringContainsString('abonnement', mb_strtolower($reponse->json('message')));
    }

    public function test_le_plan_basique_refuse_le_multi_sites()
    {
        $this->abonner($this->principal, 'basique');

        $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.sites.index'))
            ->assertForbidden();
    }

    public function test_un_abonnement_inexpire_ne_donne_plus_acces_au_multi_sites()
    {
        $this->abonner($this->principal, 'premium', 'expire');

        $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.sites.index'))
            ->assertForbidden();
    }

    public function test_le_plan_du_site_secondaire_n_ouvre_pas_le_multi_sites()
    {
        // Le groupe est en Basique : un abonnement Premium sur le site
        // secondaire ne doit rien changer, c'est le site principal qui facture.
        $secondaire = $this->creerEtablissement('Site Premium', $this->principal);
        $this->abonner($this->principal, 'basique');
        $this->abonner($secondaire, 'premium');

        $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.sites.index'))
            ->assertForbidden();
    }

    public function test_le_verrou_s_applique_a_la_modification_et_a_la_suppression()
    {
        $abonnement = $this->abonner($this->principal, 'premium');
        $site = $this->creerEtablissement('Site A Supprimer', $this->principal);

        // Retrogradation du groupe en Basique apres la creation du site
        // (l'abonnement existant change de plan, il n'y en a qu'un actif).
        $abonnement->update(['plan' => 'basique']);

        $this->actingAs($this->directeur, 'sanctum')
            ->putJson(route('api.v1.etablissement.sites.update', $site), [
                'nom'       => 'Site Renomme',
                'ville'     => 'Douala',
                'telephone' => '650000001',
                'email'     => 'site.a@test.cm',
            ])
            ->assertForbidden();

        $this->actingAs($this->directeur, 'sanctum')
            ->deleteJson(route('api.v1.etablissement.sites.destroy', $site))
            ->assertForbidden();

        $this->assertNotSame('Site Renomme', $site->fresh()->nom);
        $this->assertNotNull($site->fresh());
    }

    // ─────────────────────────────────────────────
    // I — liste et suppression
    // ─────────────────────────────────────────────

    public function test_la_liste_des_sites_est_lisible_a_plat_par_le_mobile()
    {
        $this->abonner($this->principal, 'premium');
        $this->creerEtablissement('Site Alpha', $this->principal);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.sites.index'))
            ->assertOk()
            ->json('data');

        $this->assertTrue($data['est_site_principal']);

        $noms = array_column($data['sites'], 'nom');
        $this->assertContains('Groupe Anacarde', $noms);
        $this->assertContains('Site Alpha', $noms);

        // Chaque site expose ses champs a plat ET via `site` (compatibilite).
        $site = collect($data['sites'])->firstWhere('nom', 'Site Alpha');
        $this->assertSame('Site Alpha', $site['site']['nom']);
        $this->assertArrayHasKey('nb_apprenants', $site);
        $this->assertArrayHasKey('total_encaisse', $site);
        $this->assertArrayHasKey('annee_scolaire', $site);
    }

    public function test_un_site_avec_donnees_est_refuse_a_la_suppression()
    {
        $this->abonner($this->principal, 'premium');
        $site = $this->siteAvecDonnees($this->principal, 'Site Occupe');

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->deleteJson(route('api.v1.etablissement.sites.destroy', $site))
            ->assertStatus(409);

        $this->assertSame(1, $reponse->json('data.nb_apprenants'));
        $this->assertSame(1, $reponse->json('data.nb_paiements'));
        $this->assertNotNull($site->fresh(), 'Le site ne doit pas etre supprime.');
    }

    public function test_un_site_vide_est_supprime_et_ses_comptes_suspendus()
    {
        $this->abonner($this->principal, 'premium');
        $site = $this->creerEtablissement('Site Vide', $this->principal);
        $compte = $this->creerDirecteur($site);

        $this->actingAs($this->directeur, 'sanctum')
            ->deleteJson(route('api.v1.etablissement.sites.destroy', $site))
            ->assertOk();

        $this->assertSoftDeleted('etablissements', ['id' => $site->id]);
        $this->assertTrue((bool) $compte->fresh()->suspendu);
        $this->assertNotNull($compte->fresh()->suspendu_at);
    }

    public function test_un_directeur_ne_peut_pas_toucher_un_site_d_un_autre_groupe()
    {
        $this->abonner($this->principal, 'premium');

        $autreGroupe = $this->creerEtablissement('Autre Groupe', null);
        $this->abonner($autreGroupe, 'premium');
        $siteEtranger = $this->creerEtablissement('Site Etranger', $autreGroupe);

        $this->actingAs($this->directeur, 'sanctum')
            ->deleteJson(route('api.v1.etablissement.sites.destroy', $siteEtranger))
            ->assertForbidden();

        $this->assertNotNull($siteEtranger->fresh());
    }

    public function test_le_directeur_d_un_site_secondaire_voit_le_groupe_complet()
    {
        $this->abonner($this->principal, 'premium');
        $secondaire = $this->creerEtablissement('Site Filial', $this->principal);
        $this->abonner($secondaire, 'basique');
        $this->creerEtablissement('Site Soeur', $this->principal);

        $data = $this->actingAs($this->creerDirecteur($secondaire), 'sanctum')
            ->getJson(route('api.v1.etablissement.sites.index'))
            ->assertOk()
            ->json('data');

        $this->assertFalse($data['est_site_principal']);
        $this->assertCount(3, $data['sites']);
    }

    // ─────────────────────────────────────────────
    // K — KPI par annee scolaire du site
    // ─────────────────────────────────────────────

    public function test_le_kpi_encaisse_ne_cumule_pas_les_annees_passees()
    {
        $this->abonner($this->principal, 'premium');
        $site = $this->siteAvecDonnees($this->principal, 'Site Historique');

        // Un encaissement d'une annee passee ne doit pas gonfler l'annee active.
        $apprenant = $site->apprenants()->first();
        $fraisAncienne = FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => $apprenant->frais()->first()->categorie_frais_id,
            'montant_total'     => 70000,
            'montant_paye'      => 70000,
            'statut'            => 'regle',
            'annee_scolaire'    => '2025-2026',
        ]);

        Paiement::create([
            'user_id'            => $this->directeur->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $fraisAncienne->id,
            'montant'            => 70000,
            'frais_service'      => 0,
            'montant_total_paye' => 70000,
            'frais_aangaraa'     => 0,
            'marge_edupay'       => 0,
            'mode_paiement'      => 'mtn_momo',
            'type_paiement'      => 'integral',
            'statut'             => 'valide',
            'telephone_paiement' => '650000000',
            'date_paiement'      => now(),
        ]);

        $data = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.sites.index'))
            ->assertOk()
            ->json('data');

        $kpi = collect($data['sites'])->firstWhere('nom', 'Site Historique');

        $this->assertSame(self::ANNEE, $kpi['annee_scolaire']);
        $this->assertSame(50000, $kpi['total_encaisse'], 'Seul l\'annee active doit etre comptee.');
        $this->assertSame(50000, $data['total_groupe_encaisse']);
    }
}
