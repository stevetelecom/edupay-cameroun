<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Apprenant;
use App\Models\Abonnement;
use App\Models\CategoriesFrais;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\Remboursement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Non-regression des trois correctifs de l'audit :
 *
 * 1. un compte admin desactive ne pouvait plus se connecter, et un cookie
 *    « se souvenir de moi » deja emis ne lui donnait plus acces ;
 * 2. un superviseur ne pouvait plus agir sur l'argent ni detruire de donnees ;
 * 3. le back-office web du remboursement avait moins de garde-fous que l'API,
 *    et le groupe `/api/v1/etablissement/*` n'avait pas `check.abonnement`.
 */
class CorrectionsAuditSecuriteTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────
    // 1. Compte desactive
    // ─────────────────────────────────────────────

    public function test_un_admin_desactive_ne_peut_pas_se_connecter()
    {
        $this->admin('desactive@test.cm', false);

        $this->post(route('admin.login.post'), [
            'email'    => 'desactive@test.cm',
            'password' => 'secret1234',
        ])->assertSessionHasErrors([
            'email' => 'Identifiants incorrects.',
        ]);

        // Message identique a celui d'un email inconnu : sinon la reponse
        // confirmait au attaquant que le compte existe (enumeration).
        $this->post(route('admin.login.post'), [
            'email'    => 'inconnu@test.cm',
            'password' => 'secret1234',
        ])->assertSessionHasErrors([
            'email' => 'Identifiants incorrects.',
        ]);
    }

    public function test_un_admin_actif_peut_toujours_atteindre_le_back_office()
    {
        $admin = $this->admin('actif@test.cm', true, 'super-admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_un_admin_desactive_avec_une_session_active_est_bloque()
    {
        $admin = $this->admin('session@test.cm', true, 'super-admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();

        // Suspension « par quelqu'un d'autre » en cours de session.
        Admin::whereKey($admin->id)->update(['est_actif' => false]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_la_suspension_revoque_le_cookie_se_souvenir_de_moi()
    {
        $acteur = $this->admin('acteur@test.cm', true, 'super-admin');
        $cible  = $this->admin('cookie@test.cm', true, 'superviseur');
        $avant  = $cible->remember_token;

        $this->actingAs($acteur, 'admin')
            ->patch(route('admin.admins.suspendre', $cible))
            ->assertSessionHas('success');

        $this->assertNotSame($avant, $cible->fresh()->remember_token);
        $this->assertFalse((bool) $cible->fresh()->est_actif);
    }

    public function test_un_administrateur_ne_peut_pas_se_suspendre_lui_meme()
    {
        $admin = $this->admin('lui@test.cm', true, 'super-admin');

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.admins.suspendre', $admin))
            ->assertSessionHas('error');

        $this->assertTrue((bool) $admin->fresh()->est_actif);
    }

    // ─────────────────────────────────────────────
    // 2. Separation des roles
    // ─────────────────────────────────────────────

    public function test_un_superviseur_ne_peut_pas_prelever_une_commission()
    {
        $superviseur = $this->admin('sup@test.cm', true, 'superviseur');
        [, $paiement] = $this->paiementARembourser();
        $commission  = $this->commission($paiement);

        $this->actingAs($superviseur, 'admin')
            ->patch(route('admin.commissions.prelever', $commission))
            ->assertForbidden();
    }

    public function test_un_superviseur_ne_peut_pas_supprimer_un_etablissement()
    {
        $superviseur = $this->admin('sup2@test.cm', true, 'superviseur');

        $this->actingAs($superviseur, 'admin')
            ->delete(route('admin.etablissements.destroy', $this->etablissement()))
            ->assertForbidden();
    }

    public function test_un_superviseur_ne_peut_pas_gerer_les_administrateurs()
    {
        $superviseur = $this->admin('sup3@test.cm', true, 'superviseur');

        $this->actingAs($superviseur, 'admin')
            ->get(route('admin.admins.index'))
            ->assertForbidden();
    }

    public function test_un_superviseur_ne_peut_pas_detruire_un_abonnement()
    {
        $superviseur = $this->admin('sup4@test.cm', true, 'superviseur');
        $abo         = $this->abonnement($this->etablissement());

        $this->actingAs($superviseur, 'admin')
            ->delete(route('admin.abonnements.destroy', $abo))
            ->assertForbidden();
    }

    public function test_un_superviseur_peut_encore_lire_les_etablissements()
    {
        // « Superviseur — Lecture + rapports » : la lecture reste permise.
        $superviseur = $this->admin('lecture@test.cm', true, 'superviseur');

        $this->actingAs($superviseur, 'admin')
            ->get(route('admin.etablissements.index'))
            ->assertOk();
    }

    public function test_le_comptable_plateforme_ne_peut_pas_changer_un_taux_de_commission()
    {
        $comptable = $this->admin('cp@test.cm', true, 'comptable_plateforme');

        $this->actingAs($comptable, 'admin')
            ->get(route('admin.commissions.edit', $this->etablissement()))
            ->assertForbidden();
    }

    /**
     * Le taux ne se regle plus par etablissement mais par profil
     * d'abonnement (CDC S0 #3). Les deux routes d'ecriture ecrivent dans
     * parametres_systeme : elles doivent donc etre reservees au super-admin,
     * exactement comme l'etait l'ancienne edition par etablissement.
     */
    public function test_le_comptable_plateforme_ne_peut_pas_ecrire_les_taux_de_commission()
    {
        $comptable = $this->admin('cp-taux@test.cm', true, 'comptable_plateforme');
        $avant     = \App\Models\ParametreSysteme::obtenir('taux_commission_standard');

        $this->actingAs($comptable, 'admin')
            ->patch(route('admin.commissions.taux-plans'), [
                'taux_basique'  => 0.030,
                'taux_standard' => 0.050,
                'taux_premium'  => 0.060,
            ])
            ->assertForbidden();

        $this->actingAs($comptable, 'admin')
            ->patch(route('admin.commissions.taux-global'), ['taux_global' => 0.050])
            ->assertForbidden();

        // Le role « lecture + rapports » ne touche pas non plus aux taux.
        $superviseur = $this->admin('sup-taux@test.cm', true, 'superviseur');

        $this->actingAs($superviseur, 'admin')
            ->patch(route('admin.commissions.taux-plans'), [
                'taux_basique'  => 0.030,
                'taux_standard' => 0.050,
                'taux_premium'  => 0.060,
            ])
            ->assertForbidden();

        $this->assertSame($avant, \App\Models\ParametreSysteme::obtenir('taux_commission_standard'));
    }

    // ─────────────────────────────────────────────
    // 3. Remboursements : parite web / API
    // ─────────────────────────────────────────────

    public function test_le_web_refuse_un_remboursement_d_un_paiement_non_valide()
    {
        [$directeur, $paiement] = $this->paiementARembourser();

        $paiement->update(['statut' => 'annule']);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.store'), [
                'paiement_id' => $paiement->id,
                'montant'     => 1000,
                'motif'       => 'Erreur de saisie',
            ])->assertSessionHasErrors('paiement_id');

        $this->assertSame(0, Remboursement::count());
    }

    public function test_le_web_refuse_une_seconde_demande_en_cours()
    {
        [$directeur, $paiement] = $this->paiementARembourser();

        Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => 1000,
            'motif'       => 'Deja en cours',
            'statut'      => 'en_attente',
            'initie_par'  => $directeur->id,
        ]);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.store'), [
                'paiement_id' => $paiement->id,
                'montant'     => 1000,
                'motif'       => 'Second essai',
            ])->assertSessionHasErrors('paiement_id');

        $this->assertSame(1, Remboursement::count());
    }

    public function test_le_web_refuse_une_demande_meme_si_le_paiement_nest_plus_valide()
    {
        [$directeur, $paiement] = $this->paiementARembourser();

        $demande = Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => 10000,
            'motif'       => 'Erreur de saisie',
            'statut'      => 'en_attente',
            'initie_par'  => $directeur->id,
        ]);

        // Le paiement a ete annule entre la demande et l'approbation.
        $paiement->update(['statut' => 'annule']);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.approuver', $demande))
            ->assertSessionHas('error');

        $this->assertSame('en_attente', $demande->fresh()->statut);
    }

    public function test_le_web_refuse_une_approbation_qui_depasse_le_cumul_du_paiement()
    {
        [$directeur, $paiement] = $this->paiementARembourser();

        // Scenario de derive : deux lignes dont le cumul approuve depasse le
        // paiement (donnee anterieure, import, double clic...). Le cumul est
        // revérifié a l'approbation, pas seulement a la creation.
        Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => 45000,
            'motif'       => 'Gros remboursement deja approuve',
            'statut'      => 'approuve',
            'initie_par'  => $directeur->id,
            'traite_par'  => $directeur->id,
        ]);

        $demande = Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => 10000,
            'motif'       => 'Reste a approuver',
            'statut'      => 'en_attente',
            'initie_par'  => $directeur->id,
        ]);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.approuver', $demande))
            ->assertSessionHas('error');

        $this->assertSame('en_attente', $demande->fresh()->statut);
        $this->assertSame('valide', $paiement->fresh()->statut);
    }

    public function test_le_web_approuve_un_remboursement_valide()
    {
        [$directeur, $paiement] = $this->paiementARembourser();

        $remboursement = Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => 10000,
            'motif'       => 'Erreur de saisie',
            'statut'      => 'en_attente',
            'initie_par'  => $directeur->id,
        ]);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.approuver', $remboursement))
            ->assertSessionHas('success');

        $this->assertSame('approuve', $remboursement->fresh()->statut);
    }

    public function test_le_web_signale_le_claw_back_si_le_reversement_est_deja_parti()
    {
        [$directeur, $paiement] = $this->paiementARembourser();

        // `reversementEffectue()` lit `statut === 'prelevee'`.
        $this->commission($paiement, Commission::STATUT_PRELEVEE);

        $remboursement = Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => 10000,
            'motif'       => 'Erreur de saisie',
            'statut'      => 'en_attente',
            'initie_par'  => $directeur->id,
        ]);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.approuver', $remboursement))
            ->assertSessionHas('success');

        $this->assertStringContainsString(
            'déjà été reversé',
            session('success')
        );
    }

    public function test_un_remboursement_ne_peut_pas_etrerapprove()
    {
        [$directeur, $paiement] = $this->paiementARembourser();

        $remboursement = Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => 10000,
            'motif'       => 'Erreur de saisie',
            'statut'      => 'approuve',
            'initie_par'  => $directeur->id,
            'traite_par'  => $directeur->id,
        ]);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.approuver', $remboursement))
            ->assertSessionHas('error');
    }

    public function test_une_demande_etrangere_ne_peut_pas_etre_creee()
    {
        [, $paiement] = $this->paiementARembourser();
        $directeur     = $this->directeur();
        $this->abonnerEtablissement($directeur->etablissement);

        $this->actingAs($directeur)
            ->post(route('etablissement.remboursements.store'), [
                'paiement_id' => $paiement->id,
                'montant'     => 1000,
                'motif'       => 'Paiement d\'un autre etablissement',
            ])->assertForbidden();

        $this->assertSame(0, Remboursement::count());
    }

    // ─────────────────────────────────────────────
    // 4. Abonnement sur l'API etablissement
    // ─────────────────────────────────────────────

    public function test_l_api_etablissement_refuse_sans_abonnement()
    {
        $directeur = $this->directeur();
        $this->abonnement($directeur->etablissement);

        $this->actingAs($directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertOk();

        // Expiration de l'abonnement.
        $directeur->etablissement->abonnements()->update([
            'date_fin'         => now()->subYear()->toDateString(),
            'grace_period_fin' => now()->subMonths(11)->toDateString(),
        ]);

        $reponse = $this->actingAs($directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.apprenants.index'))
            ->assertStatus(402);

        $this->assertSame('abonnement_requis', $reponse->json('code'));
    }

    public function test_l_api_etablissement_laisse_passer_le_dashboard_et_le_profil()
    {
        $directeur = $this->directeur();
        $this->abonnement($directeur->etablissement, 'basique');
        $directeur->etablissement->abonnements()->update([
            'date_fin'         => now()->subYear()->toDateString(),
            'grace_period_fin' => now()->subMonths(11)->toDateString(),
        ]);

        // Meme situation que le web (routes autorisees) : le dashboard
        // doit rester lisible, avec le bandeau d'etat.
        $this->actingAs($directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.dashboard'))
            ->assertOk();

        $this->actingAs($directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.profil'))
            ->assertOk();
    }

    // ─────────────────────────────────────────────
    // 5. Recherche d'apprenant : validation
    // ─────────────────────────────────────────────

    public function test_la_recherche_d_apprenant_refuse_un_etablissement_inexistant()
    {
        $directeur = $this->directeur();

        $this->actingAs($directeur, 'sanctum')
            ->getJson(route('api.v1.apprenants.search', ['etablissement_id' => 999999, 'q' => 'Fono']))
            ->assertStatus(422);
    }

    public function test_la_recherche_d_apprenant_refuse_une_requete_sans_limite_de_longueur()
    {
        $directeur = $this->directeur();

        $this->actingAs($directeur, 'sanctum')
            ->getJson(route('api.v1.apprenants.search', [
                'etablissement_id' => $directeur->etablissement_id,
                'q'                => str_repeat('a', 200),
            ]))
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function admin(string $email, bool $estActif, ?string $role = null): Admin
    {
        $admin = Admin::create([
            'prenom'    => 'Compte',
            'nom'       => 'Test',
            'email'     => $email,
            'password'  => Hash::make('secret1234'),
            'est_actif' => $estActif,
        ]);

        if ($role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'admin']);
            $admin->assignRole($role);
        }

        return $admin;
    }

    private function etablissement(string $suffixe = ''): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => 'AUD' . strtoupper($suffixe ?: bin2hex(random_bytes(3))),
            'nom'                   => 'Ecole Audit ' . $suffixe,
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000' . rand(100, 999),
            'email'                 => 'audit' . strtolower($suffixe ?: bin2hex(random_bytes(3))) . '@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);
    }

    private function abonnement(Etablissement $etablissement, string $plan = 'premium'): Abonnement
    {
        return Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => $plan,
            'montant_mensuel'  => 10000,
            'date_debut'       => now()->subMonth()->toDateString(),
            'date_fin'         => now()->addMonths(10)->toDateString(),
            'grace_period_fin' => now()->addMonths(11)->toDateString(),
            'statut'           => 'actif',
        ]);
    }

    private function directeur(): User
    {
        Role::firstOrCreate(['name' => 'directeur', 'guard_name' => 'web']);

        $directeur = User::factory()->create([
            'etablissement_id' => $this->etablissement()->id,
        ]);
        $directeur->assignRole('directeur');

        return $directeur;
    }

    private function paiementARembourser(): array
    {
        $directeur = $this->directeur();
        $this->abonnerEtablissement($directeur->etablissement);

        $apprenant = Apprenant::create([
            'etablissement_id' => $directeur->etablissement_id,
            'nom'              => 'NGONO',
            'prenom'           => 'Test',
            'matricule'        => 'AUD-001',
            'classe'           => 'Tle A',
            'actif'            => true,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => CategoriesFrais::create([
                'etablissement_id' => $directeur->etablissement_id,
                'nom'              => 'Scolarite',
                'montant_total'    => 50000,
                'annee_scolaire'   => '2026-2027',
            ])->id,
            'montant_total'     => 50000,
            'montant_paye'      => 0,
            'statut'            => 'impaye',
            'annee_scolaire'    => '2026-2027',
        ]);

        $paiement = Paiement::create([
            'user_id'            => $directeur->id,
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

        return [$directeur, $paiement];
    }

    private function commission(Paiement $paiement, string $statut = Commission::STATUT_CALCULEE): Commission
    {
        return Commission::create([
            'paiement_id'         => $paiement->id,
            'etablissement_id'    => $paiement->apprenant->etablissement_id,
            'taux'                => 0.05,
            'montant_transaction' => 50000,
            'montant_commission'  => 2500,
            'montant_net_etablissement' => 47500,
            'statut'              => $statut,
        ]);
    }

}
