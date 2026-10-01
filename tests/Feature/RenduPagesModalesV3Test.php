<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Admin;
use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rendu reel des pages dont les modals ont ete convertis au systeme
 * ep-modal v3 (CSS centralise dans public/css/edupay-pages.css, blocs
 * ep-modal-head / ep-modal-body / ep-modal-foot, boutons btn-p / btn-o /
 * btn-r, recapitulatif ep-m-conf).
 *
 * Ces pages forment un seul lot : verifier le rendu evite de pousser un
 * etat ou une page renvoie 500, ouffiche une cle de traduction brute, ou
 * perd un modal parce que son @push('modals') n'est plus repris.
 */
class RenduPagesModalesV3Test extends TestCase
{
    use RefreshDatabase;

    private function payeur(string $profil = 'parent'): User
    {
        Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);

        $user = User::create([
            'nom'       => 'Njoya',
            'prenom'    => 'Ibrahim',
            'telephone' => '6900000' . random_int(10, 99),
            'email'     => 'rendu-payeur-' . uniqid() . '@test.cm',
            'password'  => Hash::make('secret1234'),
            'profil'    => $profil,
        ]);
        $user->assignRole('parent');

        return $user;
    }

    /** Le role etablissement est porte par User, mais la colonne profil est un enum(parent, eleve, etudiant). */
    private function directeur(Etablissement $etab): User
    {
        Role::firstOrCreate(['name' => 'directeur', 'guard_name' => 'web']);

        $user = User::create([
            'nom'       => 'Nkolo',
            'prenom'    => 'Alice',
            'telephone' => '6900001' . random_int(10, 99),
            'email'     => 'rendu-directeur-' . uniqid() . '@test.cm',
            'password'  => Hash::make('secret1234'),
            'profil'    => 'parent',
        ]);
        $user->etablissement_id = $etab->id;
        $user->save();
        $user->assignRole('directeur');

        return $user;
    }

    private function admin(): Admin
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        $admin = Admin::create([
            'prenom'    => 'Super',
            'nom'       => 'Admin',
            'email'     => 'rendu-admin-' . uniqid() . '@test.cm',
            'telephone' => '6900002' . random_int(10, 99),
            'password'  => Hash::make('secret1234'),
            'est_actif' => true,
        ]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function etablissement(): Etablissement
    {
        static $n = 0;
        $n++;

        return Etablissement::create([
            'nom'                => 'Ecole Rendu V3 ' . $n,
            'code_etablissement' => 'REND-V3-' . $n,
            'type'               => 'privee',
            'statut_juridique'   => 'privee',
            'statut'             => 'actif',
            'region'             => 'Douala',
            'ville'              => 'Douala',
            'telephone'          => '650000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'email'              => 'rendu-etab-' . uniqid() . '@test.cm',
            'logo'               => 'logos/rendu-v3.svg',
        ]);
    }

    private function abonnementActif(Etablissement $etab): Abonnement
    {
        return Abonnement::create([
            'etablissement_id' => $etab->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => now()->subMonth()->toDateString(),
            'date_fin'         => now()->addMonths(6)->toDateString(),
            'grace_period_fin' => now()->addMonths(6)->addDays(7)->toDateString(),
            'statut'           => 'actif',
        ]);
    }

    private function apprenant(Etablissement $etab, User $proprietaire): Apprenant
    {
        $apprenant = Apprenant::create([
            'etablissement_id'         => $etab->id,
            'prenom'                   => 'Ibrahim',
            'nom'                      => 'Njoya',
            'classe'                   => '6e A',
            'matricule'                => 'REND-' . $etab->code_etablissement . '-001',
            'statut_paiement'          => 'impaye',
            'actif'                    => true,
            'source'                   => 'payeur',
            'valide_par_etablissement' => true,
        ]);

        $proprietaire->apprenants()->attach($apprenant->id, ['lien' => 'parent']);

        return $apprenant;
    }

    /**
     * Aucune cle de traduction ne doit apparaitre brute dans le HTML rendu.
     * On ne liste que les namespaces de traduction utilises par les vues
     * concernees : `app.` catcherait sinon resources/css/app.css.
     */
    private function assertAucuneCleBrute(string $html, string $page): void
    {
        preg_match_all('/\b(?:payeur|admin|etablissement|messages)\.[a-z0-9_]+\b/', $html, $trouvees);

        $this->assertSame(
            [],
            array_values(array_unique($trouvees[0])),
            $page . ' : cle(s) de traduction non traduite(s) dans le HTML rendu'
        );
    }

    private function assertModalV3(string $html, string $ancre, string $page): void
    {
        $this->assertStringContainsString('id="' . $ancre . '"', $html, $page . ' : modal absent');
        $this->assertStringContainsString('ep-modal-overlay', $html, $page . ' : overlay ep-modal absent');
        $this->assertStringContainsString('ep-modal-head', $html, $page . ' : en-tete ep-modal absent');
        $this->assertStringContainsString('ep-modal-close', $html, $page . ' : bouton de fermeture absent');
    }

    public function test_la_page_onboarding_payeur_se_rend_avec_le_logo(): void
    {
        Storage::fake('public');
        $user = $this->payeur();
        $this->etablissement();

        $html = $this->actingAs($user)
            ->get(route('payeur.onboarding'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storage/logos/rendu-v3.svg', $html,
            'le logo de l etablissement doit etre rendu dans l annuaire');
        $this->assertAucuneCleBrute($html, 'onboarding');
    }

    public function test_le_dashboard_payeur_se_rend_avec_le_modal_de_dessassociation(): void
    {
        // profil eleve = vue Solo : c'est la seule qui renseigne $monDossier,
        // donc la seule ou le modal de desaffectation est rendu.
        $user      = $this->payeur('eleve');
        $etab      = $this->etablissement();
        $apprenant = $this->apprenant($etab, $user);

        $html = $this->actingAs($user)
            ->get(route('payeur.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertModalV3($html, 'modal-detacher-apprenant', 'dashboard');
        $this->assertModalV3($html, 'modal-rattacher', 'dashboard');
        $this->assertStringContainsString('ep-m-conf-carte', $html,
            'le recapitulatif v3 doit remplacer l ancien bloc de confirmation');
        $this->assertStringContainsString($apprenant->id . '/frais', $html,
            'le dossier rattache doit etre liste');
        $this->assertAucuneCleBrute($html, 'dashboard');
    }

    public function test_la_page_frais_apprenant_se_rend(): void
    {
        $user      = $this->payeur('eleve');
        $etab      = $this->etablissement();
        $apprenant = $this->apprenant($etab, $user);

        $html = $this->actingAs($user)
            ->get(route('payeur.frais.apprenant', $apprenant))
            ->assertOk()
            ->getContent();

        $this->assertModalV3($html, 'modal-detacher-apprenant', 'frais');
        $this->assertAucuneCleBrute($html, 'frais');
    }

    public function test_la_fiche_apprenant_etablissement_se_rend(): void
    {
        $etab      = $this->etablissement();
        $this->abonnementActif($etab);
        $directeur = $this->directeur($etab);
        $apprenant = $this->apprenant($etab, $this->payeur());

        $html = $this->actingAs($directeur)
            ->get(route('etablissement.apprenants.show', $apprenant))
            ->assertOk()
            ->getContent();

        $this->assertModalV3($html, 'modal-desaffecter', 'fiche apprenant');
        $this->assertStringContainsString('ep-m-conf-carte', $html, 'le recapitulatif v3 doit etre present');
        $this->assertAucuneCleBrute($html, 'fiche apprenant');
    }

    public function test_la_liste_des_abonnements_admin_se_rend(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.abonnements.index'))
            ->assertOk()
            ->getContent();

        $this->assertModalV3($html, 'modal-new-abo', 'abonnements');
        $this->assertModalV3($html, 'modal-renew-abo', 'abonnements');
        $this->assertModalV3($html, 'modal-edit-abo', 'abonnements');
        $this->assertAucuneCleBrute($html, 'abonnements');
    }

    public function test_la_liste_des_admins_se_rend(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.admins.index'))
            ->assertOk()
            ->getContent();

        $this->assertModalV3($html, 'modal-create-admin', 'admins');
        $this->assertModalV3($html, 'modal-delete-admin', 'admins');
        $this->assertModalV3($html, 'modal-suspend-admin', 'admins');
        $this->assertModalV3($html, 'modal-activer-admin', 'admins');
        $this->assertAucuneCleBrute($html, 'admins');
    }

    /**
     * Regression : les 3 modales de confirmation admin injectaient leur
     * <span id="..."> via e(), qui echappait le fragment HTML LUI-MEME. Le
     * rendu HTML contenait donc &lt;span id=&quot;...&quot;&gt; en texte
     * litteral : aucun element n'existait, getElementById() renvoyait null,
     * et le nom de l'admin n'apparait JAMAIS avant de confirmer.
     * Le nom injecte par le JS est pose en textContent : pas d'injection.
     */
    public function test_les_modales_de_confirmation_admin_exposent_leur_span_de_nom(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.admins.index'))
            ->assertOk()
            ->getContent();

        foreach (['delete-admin-nom', 'suspend-admin-nom', 'activate-admin-nom'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html,
                'le span #' . $id . ' doit exister dans le DOM : sans lui le JS '
                . 'pose le nom sur un element null et le nom ne s affiche jamais');
            $this->assertStringNotContainsString('&lt;span id=&quot;' . $id . '&quot;', $html,
                'le fragment ne doit pas etre echappe en texte : e() avait echappe le HTML lui-meme');
        }
    }

    /** Meme regression sur la suppression d'abonnement (span #delete-abo-nom). */
    public function test_le_modal_de_suppression_abonnement_expose_son_span_de_nom(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.abonnements.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="delete-abo-nom"', $html);
        $this->assertStringNotContainsString('&lt;span id=&quot;delete-abo-nom&quot;', $html);
    }

    public function test_le_modal_de_rattachement_est_inclus_et_complet_sur_le_dashboard(): void
    {
        $user = $this->payeur();
        $this->etablissement();

        $html = $this->actingAs($user)
            ->get(route('payeur.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('m-etab-liste', $html, 'la liste des etablissements doit etre presente');
        $this->assertStringContainsString('m-onb-form', $html, 'le formulaire de rattachement doit etre present');
        $this->assertStringContainsString('m-btn-confirmer', $html, 'le bouton Confirmer doit etre present');
        $this->assertStringContainsString('m-btn-changer-etab', $html, 'le bouton Changer doit etre present');
        $this->assertAucuneCleBrute($html, 'modal rattachement');
    }

    public function test_le_modal_de_rattachement_est_inclus_sur_la_page_mes_enfants(): void
    {
        $user = $this->payeur();
        $this->apprenant($this->etablissement(), $user);

        $html = $this->actingAs($user)
            ->get(route('payeur.mes-enfants'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('m-etab-liste', $html);
        $this->assertStringContainsString('m-onb-form', $html);
        $this->assertAucuneCleBrute($html, 'mes enfants');
    }

    public function test_les_pages_avec_modal_ne_perdent_pas_leur_modal(): void
    {
        // Regression d'architecture : le modal est pousse via @push('modals').
        // Si le layout cesse de faire @stack('modals'), le HTML disparait
        // sans erreur et le bouton d'ouverture ne ouvre plus rien.
        $layout = file_get_contents(resource_path('views/layouts/payeur.blade.php'));
        $this->assertStringContainsString("@stack('modals')", $layout,
            'le layout payeur doit rendre la pile modals');

        $user = $this->payeur();
        $this->apprenant($this->etablissement(), $user);

        $html = $this->actingAs($user)
            ->get(route('payeur.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("epModal.open('modal-rattacher')", $html,
            'le bouton d ouverture du modal doit etre rendu');
    }
}
