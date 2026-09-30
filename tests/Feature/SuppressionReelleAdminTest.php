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
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verification de la suppression REELLE (back-office super admin).
 *
 * Constat initial : le bouton « Supprimer » ne supprimait rien. Les deux
 * controleurs appelaient `$modele->delete()`, c'est-a-dire un SOFT DELETE qui
 * pose `deleted_at` : la ligne restait dans `users` / `etablissements`. Le
 * compte disparaissait des listes parce que les queries Eloquent filtrent sur
 * `deleted_at IS NULL`, ce qui donnait l'illusion d'une suppression effective.
 *
 * Une vraie suppression etait de surcroit impossible : `paiements.user_id`,
 * `notifications.user_id` et `remboursements.initie_par` referencaient `users`
 * en RESTRICT + NOT NULL. La migration 2026_09_30_100000 les passe en
 * `ON DELETE SET NULL` pour detacher le releve financier sans le detruire
 * (CDC §3.2 : tracabilite complete et infalsifiable des transactions).
 *
 * Regle metier imposee : un payeur ne peut etre supprime que s'il n'a aucun
 * frais attribue, ou si tous ses frais sont deja regles.
 */
class SuppressionReelleAdminTest extends TestCase
{
    use RefreshDatabase;

    private const ANNEE = '2026-2027';

    private Etablissement $etab;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $admin = Admin::create([
            'prenom'  => 'S',
            'nom'     => 'Suppression',
            'email'   => 'suppression-cms@test.cm',
            'password' => bcrypt('secret1234'),
        ]);
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'admin');

        $this->etab = Etablissement::create([
            'code_etablissement' => 'ETAB-SUPPR',
            'nom'                => 'Ecole Suppression',
            'email'              => 'suppr@test.cm',
            'telephone'          => '690000003',
            'type'               => 'prive',
            'ville'              => 'Douala',
            'statut_juridique'   => 'prive_laic',
            'region'             => 'littoral',
            'taux_commission'    => 0.001,
        ]);
    }

    // ─────────────────────────────────────────────
    // Payeur — suppression réelle
    // ─────────────────────────────────────────────

    public function test_payeur_sans_frais_est_reellement_efface_de_la_base(): void
    {
        $payeur = $this->creerPayeur('Jean Sans Frais');
        $id     = $payeur->id;

        $this->deleteJson(route('admin.payeurs.destroy', $payeur))
            ->assertOk()
            ->assertJson(['success' => true]);

        // La preuve : la ligne n'est plus dans `users`, pas meme en soft delete.
        $this->assertFalse(
            DB::table('users')->where('id', $id)->exists(),
            'La ligne du compte doit avoir disparu de la table users.'
        );
    }

    public function test_payeur_ayant_paye_tous_ses_frais_est_supprime_et_ses_paiements_sont_conserves(): void
    {
        $payeur   = $this->creerPayeur('Marie Tout Paye');
        $apprenant = $this->creerApprenant($payeur);
        $frais     = $this->creerFrais($apprenant, 100000, 100000, 'regle');
        $paiement  = $this->creerPaiement($payeur, $apprenant, $frais, 100000);

        $idPaiement = $paiement->id;
        $idPayeur   = $payeur->id;

        $this->deleteJson(route('admin.payeurs.destroy', $payeur))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse(DB::table('users')->where('id', $idPayeur)->exists());

        // Le releve financier survit a la disparition de la personne : le CDC
        // exige une tracabilite complete et infalsifiable des transactions.
        $this->assertTrue(
            DB::table('paiements')->where('id', $idPaiement)->exists(),
            'Le paiement doit rester en base, détaché du compte supprimé.'
        );
        $this->assertNull(
            DB::table('paiements')->where('id', $idPaiement)->value('user_id'),
            'Le paiement conservé doit être détaché (user_id = NULL).'
        );
        $this->assertEquals(
            100000,
            DB::table('paiements')->where('id', $idPaiement)->value('montant'),
            'Le montant encaissé doit rester intact pour l\'audit.'
        );
    }

    public function test_payeur_avec_frais_impayes_ne_peut_pas_etre_supprime(): void
    {
        $payeur    = $this->creerPayeur('Paul Endette');
        $apprenant = $this->creerApprenant($payeur);
        $this->creerFrais($apprenant, 100000, 30000, 'partiel');
        $idPayeur = $payeur->id;

        $this->deleteJson(route('admin.payeurs.destroy', $payeur))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertTrue(
            DB::table('users')->where('id', $idPayeur)->exists(),
            'Un payeur qui doit encore 70 000 FCFA ne peut pas être effacé.'
        );
    }

    public function test_payeur_avec_paiement_en_attente_ne_peut_pas_etre_supprime(): void
    {
        $payeur    = $this->creerPayeur('Alice En Attente');
        $apprenant = $this->creerApprenant($payeur);
        $frais     = $this->creerFrais($apprenant, 50000, 50000, 'regle');
        $this->creerPaiement($payeur, $apprenant, $frais, 50000, 'en_attente');

        $idPayeur = $payeur->id;

        $this->deleteJson(route('admin.payeurs.destroy', $payeur))
            ->assertStatus(422);

        $this->assertTrue(DB::table('users')->where('id', $idPayeur)->exists());
    }

    public function test_suppression_groupee_refuse_le_mixte_et_ne_supprime_que_les_comptes_soldes(): void
    {
        $solde = $this->creerPayeur('Solde Global');
        $this->creerFrais($this->creerApprenant($solde), 20000, 20000, 'regle');

        $endette = $this->creerPayeur('Encore Endette');
        $this->creerFrais($this->creerApprenant($endette), 20000, 0, 'impaye');

        $vide = $this->creerPayeur('Aucun Frais');

        $this->deleteJson(route('admin.payeurs.bulkDestroy'), [
            'ids' => [$solde->id, $endette->id, $vide->id],
        ])->assertOk();

        $this->assertFalse(DB::table('users')->where('id', $solde->id)->exists());
        $this->assertFalse(DB::table('users')->where('id', $vide->id)->exists());
        $this->assertTrue(
            DB::table('users')->where('id', $endette->id)->exists(),
            'Le compte endetté doit survivre à une suppression groupée.'
        );
    }

    public function test_une_session_active_est_fermee_a_la_suppression(): void
    {
        $payeur = $this->creerPayeur('Marie Connectee');
        DB::table('sessions')->insert([
            'id'           => 'session-test-1',
            'user_id'      => $payeur->id,
            'ip_address'   => '127.0.0.1',
            'user_agent'   => 'test',
            'payload'      => 'x',
            'last_activity' => now()->timestamp,
        ]);

        $this->deleteJson(route('admin.payeurs.destroy', $payeur))->assertOk();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $payeur->id)->count());
    }

    // ─────────────────────────────────────────────
    // Etablissement — réelle si zéro activité, archive sinon
    // ─────────────────────────────────────────────

    public function test_etablissement_sans_activite_est_reellement_efface(): void
    {
        $etab = $this->creerEtablissement('Ecole Inutilisee');
        $id   = $etab->id;

        $this->deleteJson(route('admin.etablissements.destroy', $etab))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse(
            DB::table('etablissements')->where('id', $id)->exists(),
            'Une école sans élève, paiement ni commission doit être effacée de la table.'
        );
    }

    public function test_etablissement_avec_eleves_est_archive_et_non_supprime(): void
    {
        $etab = $this->creerEtablissement('Ecole Active');
        $this->creerApprenant(null, $etab);
        $id = $etab->id;

        $this->deleteJson(route('admin.etablissements.destroy', $etab))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue(
            DB::table('etablissements')->where('id', $id)->exists(),
            'Une école avec des élèves doit être archivée, pas effacée.'
        );
        $this->assertNotNull(
            DB::table('etablissements')->where('id', $id)->value('deleted_at'),
            'L\'archivage doit poser deleted_at.'
        );
    }

    public function test_etablissement_avec_commission_est_archive(): void
    {
        $etab = $this->creerEtablissement('Ecole Avec Commission');
        $etab->forceFill(['taux_commission' => 0.023])->save();
        Commission::create([
            'paiement_id'          => $this->creerPaiementComplet()->id,
            'etablissement_id'     => $etab->id,
            'montant_transaction'  => 10000,
            'taux'                 => 0.023,
            'montant_commission'   => 230,
            'statut'               => 'calculee',
        ]);

        $id = $etab->id;

        $this->deleteJson(route('admin.etablissements.destroy', $etab))->assertOk();

        $this->assertTrue(DB::table('etablissements')->where('id', $id)->exists());
        $this->assertNotNull(DB::table('etablissements')->where('id', $id)->value('deleted_at'));
    }

    public function test_suppression_groupee_etablissements_annonce_la_part_archivee(): void
    {
        $vide  = $this->creerEtablissement('Ecole Vide');
        $active = $this->creerEtablissement('Ecole Avec Eleves');
        $this->creerApprenant(null, $active);

        $reponse = $this->deleteJson(route('admin.etablissements.bulkDestroy'), [
            'ids' => [$vide->id, $active->id],
        ])->assertOk();

        $this->assertStringContainsString('1', $reponse->json('message'));
        $this->assertFalse(DB::table('etablissements')->where('id', $vide->id)->exists());
        $this->assertTrue(DB::table('etablissements')->where('id', $active->id)->exists());
    }

    // ─────────────────────────────────────────────
    // Non-régression : le bouton ne peut plus échouer silencieusement
    // ─────────────────────────────────────────────

    public function test_le_bouton_supprimer_etablissement_peut_s_ouvrir(): void
    {
        $this->creerEtablissement('Ecole Pour Clic');

        $html = $this->get(route('admin.etablissements.index'))->assertOk()->getContent();

        // Regression du 30/09/2026 : le placeholder `:nom` de la traductions etait
        // passe dans `e()`, qui echappait le <strong>. L'element cible n'existait
        // donc pas dans le DOM et `ouvrirSuppression()` levait un TypeError sur
        // `null.textContent` AVANT d'appeler `epModal.open()` : le bouton
        // supprimait l'affichage de la modale, donc ne faisait rien.
        $this->assertStringContainsString(
            'id="supprimer-etab-check-nom"',
            $html,
            'La cible du libellé de confirmation doit exister dans le DOM.'
        );
    }

    public function test_le_bouton_supprimer_payeur_exige_une_confirmation_ecrite(): void
    {
        $this->creerPayeur('Marie Confirmation');

        $html = $this->get(route('admin.payeurs.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="supprimer-payeur-check-nom"', $html);
        $this->assertStringContainsString('id="btn-supprimer-payeur-confirme" disabled', $html);
    }

    public function test_le_tableau_etablissements_reste_defilant_et_reactif(): void
    {
        $this->creerEtablissement('Ecole Style');

        $html = $this->get(route('admin.etablissements.index'))->assertOk()->getContent();

        // Sans enveloppe scrollable, le tableau deborde de sa carte sur mobile.
        $this->assertStringContainsString('overflow-x-auto', $html);
        // Grille de KPI : 2 colonnes sur mobile, 4 seulement sur grand ecran
        // (memes paliers que le tableau de bord et la page payeurs).
        $this->assertStringContainsString('grid grid-cols-2 lg:grid-cols-4', $html);
        // `flex-1` seul laissait le champ de recherche sereduire a rien sur
        // mobile : il prend toute la largeur en dessous de `sm`.
        $this->assertStringContainsString('w-full sm:w-auto sm:flex-1', $html);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function creerEtablissement(string $nom): Etablissement
    {
        return Etablissement::create([
            'code_etablissement' => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                => $nom,
            'email'              => strtolower(str_replace(' ', '', $nom)) . '@test.cm',
            'telephone'          => '690000000',
            'type'               => 'prive',
            'ville'              => 'Douala',
            'statut_juridique'   => 'prive_laic',
            'region'             => 'littoral',
            'taux_commission'    => 0.001,
        ]);
    }

    private function creerPayeur(string $nom): User
    {
        return User::factory()->create([
            'profil' => 'parent',
            'nom'    => $nom,
            'prenom' => 'Test',
        ]);
    }

    private function creerApprenant(?User $payeur = null, ?Etablissement $etab = null): Apprenant
    {
        $apprenant = Apprenant::create([
            'etablissement_id'         => ($etab ?? $this->etab)->id,
            'nom'                      => 'Eleve',
            'prenom'                   => 'Test',
            'classe'                   => '6e A',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        if ($payeur) {
            $apprenant->parents()->attach($payeur->id, ['lien' => 'pere']);
        }

        return $apprenant;
    }

    private function creerFrais(Apprenant $apprenant, float $total, float $paye, string $statut): FraisApprenant
    {
        return FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => CategoriesFrais::create([
                'etablissement_id' => $apprenant->etablissement_id,
                'nom'              => 'Scolarite ' . $apprenant->id,
                'montant_total'    => $total,
                'annee_scolaire'   => self::ANNEE,
            ])->id,
            'montant_total'    => $total,
            'montant_paye'     => $paye,
            'statut'           => $statut,
            'annee_scolaire'   => self::ANNEE,
        ]);
    }

    private function creerPaiement(
        User $payeur,
        Apprenant $apprenant,
        FraisApprenant $frais,
        float $montant,
        string $statut = 'valide'
    ): Paiement {
        return Paiement::create([
            'reference'          => 'PAY-' . strtoupper(bin2hex(random_bytes(4))),
            'user_id'            => $payeur->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => $montant,
            'mode_paiement'      => 'mtn_momo',
            'operateur'          => 'MTN',
            'statut'             => $statut,
        ]);
    }

    /**
     * Le detail d'un payeur doit proposer la suppression, pas seulement la
     * liste. Le bouton est un onclick inline qui appelle
     * ouvrirSuppressionPayeur() : cette vue etant injectee par innerHTML, un
     * <script> ou un @push n'y serait pas execute. On verifie donc que la vue
     * complete reste appelable seule (route show) et que le bouton y est bien
     * present avec le bon couple id / nom.
     */
    public function test_le_detail_d_un_payeur_propose_la_suppression(): void
    {
        $payeur = $this->creerPayeur('Detail Supprimable');

        $this->get(route('admin.payeurs.show', $payeur))
            ->assertOk()
            ->assertSee('ouvrirSuppressionPayeur', false)
            ->assertSee(__('admin.supprimer_compte'))
            ->assertSee((string) $payeur->id);
    }

    /**
     * Sanity check sur le flux complet : le bouton ouvre le modal, la
     * suppression reelleaboutit, et la page hote ferme aussi le detail
     * (sinon le payeur supprime resterait affiche derriere le modal).
     */
    public function test_le_modal_de_suppression_ferme_le_detail_apres_succes(): void
    {
        $this->get(route('admin.payeurs.index'))
            ->assertOk()
            // Le modal de confirmation existe sur la page hote.
            ->assertSee('modal-supprimer-payeur', false)
            // Et le detail se ferme apres un DELETE reussi.
            ->assertSee('epDetailApresSuppression', false);
    }

    /**
     * Paiement complet (avec son etablissement) pour le test des commissions.
     */
    private function creerPaiementComplet(): Paiement
    {
        $payeur    = $this->creerPayeur('Commission Payeur');
        $apprenant = $this->creerApprenant($payeur);
        $frais     = $this->creerFrais($apprenant, 10000, 10000, 'regle');

        return $this->creerPaiement($payeur, $apprenant, $frais, 10000);
    }
}
