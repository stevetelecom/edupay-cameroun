<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Le modal « Rattacher un apprenant » a ete repris en maquette v3 (styles
 * ep-*, theme sombre, etape de confirmation). Ces tests verrouillent deux
 * choses qui ont casse successivement :
 *
 *  1. le modal doit exister ETRE OUVRABLE sur les DEUX ecrans payeur
 *     (tableau de bord ET mes-enfants) — le bouton d'appel est le meme, mais
 *     le modal est inclus deux fois dans le projet, donc un oubli sur une
 *     seule page donne un bouton mort ;
 *  2. la liste des etablissements est masquee par defaut et ne doit
 *     s'ouvrir QUE sur action de l'utilisateur. Avant la v3, un `onfocus`
 *     inline laOuvrait au focus ; si l'agent l'a retire sans remettre
 *     l'ouverture ailleurs, la recherche devient inutilisable.
 */
class ModalRattacherFrontendV3Test extends TestCase
{
    use RefreshDatabase;

    private function payeur(string $profil = 'parent'): User
    {
        Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'eleve', 'guard_name' => 'web']);

        $etab = Etablissement::create([
            'nom'                => 'Ecole V3',
            'code_etablissement' => 'V3-TEST',
            'type'               => 'privee',
            'statut_juridique'   => 'privee',
            'region'             => 'Douala',
            'ville'              => 'Douala',
            'telephone'          => '650000000',
            'email'              => 'v3@test.cm',
        ]);

        $user = User::create([
            'nom'      => 'Payeur',
            'prenom'   => 'Test',
            'telephone' => '690000001',
            'email'    => 'payeur.v3@test.cm',
            'password' => Hash::make('secret1234'),
            'profil'   => $profil,
        ]);

        $user->assignRole($profil === 'etudiant' ? 'eleve' : 'parent');

        // Le payeur doit avoir deja un dossier rattache : c'est le contexte
        // « deja connecte » que le modal sert a etendre.
        $apprenant = Apprenant::create([
            'etablissement_id'         => $etab->id,
            'nom'                      => 'Njoya',
            'prenom'                   => 'Ibrahim',
            'classe'                   => '6eme',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
        $apprenant->parents()->attach($user->id, ['lien' => 'pere']);

        return $user;
    }

    /**
     * On renvoie le NOM de la route, pas son URL : un data provider est
     * execute a la decouverte des tests, avant que le conteneur Laravel ne
     * soit demarre, et un appel a route() la-bas echoue sur « Target class
     * [url] does not exist ». La resolution se fait donc dans le test.
     *
     * @return array<string, array{0: string}>
     */
    public static function ecransPayeur(): array
    {
        return [
            'tableau de bord' => ['payeur.dashboard'],
            'mes enfants'     => ['payeur.mes-enfants'],
        ];
    }

    private static function urlDashboard(): string
    {
        return route('payeur.dashboard');
    }

    private static function urlMesEnfants(): string
    {
        return route('payeur.mes-enfants');
    }

    #[DataProvider('ecransPayeur')]
    public function test_le_modal_est_inclus_et_ouvrable_sur_chaque_ecran(string $routeName): void
    {
        $user = $this->payeur();
        $url = route($routeName);

        $r = $this->actingAs($user)->get($url);

        $r->assertOk();

        // Le conteneur du modal doit etre present exactement UNE fois : un
        // double include dupliquerait les id et le MutationObserver de
        // mResetComplet() s'abonnerait deux fois.
        $this->assertSame(1, substr_count($r->getContent(), 'id="modal-rattacher"'),
            'Le modal doit etre inclus une seule fois sur ' . $url);

        // Et il doit etre ouvrable : un bouton appelant epModal.open.
        $this->assertStringContainsString(
            "epModal.open('modal-rattacher')",
            $r->getContent(),
            'Aucun bouton n ouvre le modal sur ' . $url
        );
    }

    #[DataProvider('ecransPayeur')]
    public function test_le_script_du_modal_est_charge_avec_ses_fonctions(string $routeName): void
    {
        $user = $this->payeur();
        $url = route($routeName);

        $html = $this->actingAs($user)->get($url)->getContent();

        // Les fonctions que les boutons et champs appellent inline.
        foreach (['mFiltrerEtabs', 'mListeVisible', 'mResetComplet'] as $fn) {
            $this->assertStringContainsString('function ' . $fn, $html,
                $fn . '() est absent de ' . $url);
        }

        // mListeVisible doit exister reellement : c'est elle qui remplace le
        // onfocus inline retire en v3.
        $this->assertMatchesRegularExpression(
            '/function mListeVisible\s*\(\s*\w*\s*\)\s*\{/',
            $html,
            'mListeVisible() doit ouvrir/fermer la liste des etablissements'
        );
    }

    public function test_la_liste_etablissements_est_masquee_jusqu_a_action(): void
    {
        $user = $this->payeur();

        $html = $this->actingAs($user)->get(self::urlDashboard())->getContent();

        // v3 : la liste ne doit pas deborder du modal au chargement. Elle est
        // masquee en dur dans le partial, donc aucun onfocus inline ne doit
        // rester non plus.
        $this->assertMatchesRegularExpression(
            '/id="m-etab-liste"[^>]*display:\s*none/',
            $html,
            'La liste des etablissements doit etre masquee par defaut'
        );
        $this->assertStringNotContainsString(
            "onfocus=\"document.getElementById('m-etab-liste')",
            $html,
            'Le onfocus inline de la v2 ne doit plus rester : il ouvrait la liste sans mListeVisible'
        );
    }

    public function test_il_y_a_une_seule_liste_par_id_dans_chaque_page(): void
    {
        $user = $this->payeur();

        foreach ([self::urlDashboard(), self::urlMesEnfants()] as $url) {
            $html = $this->actingAs($user)->get($url)->getContent();

            foreach (['m-etab-liste', 'm-apprenant-liste', 'm-h-apprenant-id'] as $id) {
                $this->assertSame(1, substr_count($html, 'id="' . $id . '"'),
                    'id ' . $id . ' duplique sur ' . $url);
            }
        }
    }

    public function test_le_titre_du_modal_s_adapte_au_profil_solo(): void
    {
        // Un eleve/etudiant voit le libelle « solo », pas celui du rattachement
        // parental. On compare le texte RENDU (traduit), car Blade resout le
        // @lang : chercher la cle source dans le HTML ne prouve rien.
        $solo = $this->payeur('etudiant');
        $htmlSolo = $this->actingAs($solo)->get(self::urlDashboard())->getContent();

        $this->assertStringContainsString('Rattacher mon profil', $htmlSolo,
            'Le profil solo doit proposer de rattacher son propre profil');
    }

    public function test_le_modal_presente_les_etablissements_autorises(): void
    {
        $user = $this->payeur();

        $html = $this->actingAs($user)->get(self::urlDashboard())->getContent();

        // L'annuaire porte le nom de l'etablissement, et la structure attendue
        // par mSelectionnerEtab() (data-id / data-nom / cases a cocher).
        $this->assertStringContainsString('Ecole V3', $html);
        $this->assertStringContainsString('m-etab-item', $html);
        $this->assertStringContainsString('m-etab-check', $html);
    }

    public function test_le_modal_envoie_le_formulaire_vers_onboarding(): void
    {
        $user = $this->payeur();

        $html = $this->actingAs($user)->get(self::urlDashboard())->getContent();

        // C'est l'action du formulaire qui fait le rattachement reel : si elle
        // change, le modal devient purement decoratif.
        $this->assertStringContainsString('id="m-onb-form"', $html);
        $this->assertStringContainsString(
            'espace/onboarding',
            $html,
            'Le formulaire doit pointer vers la route onboarding'
        );
    }

    public function test_les_champs_requis_du_rattachement_sont_presents(): void
    {
        $user = $this->payeur();

        $html = $this->actingAs($user)->get(self::urlDashboard())->getContent();

        foreach ([
            'm-h-etab-id',
            'm-h-apprenant-id',
            'm-apprenant-search',
            'm-lien',
        ] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html,
                'Le champ ' . $id . ' est necessaire au rattachement');
        }
    }
}
