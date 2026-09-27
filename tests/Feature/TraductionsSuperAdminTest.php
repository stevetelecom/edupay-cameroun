<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Etablissement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cote super admin, une interface en francais ne doit jamais laisser fuir de
 * l'anglais, ni cle de traduction brute.
 *
 * Deux defauts distincts etient cumules :
 *
 *  1. `__('admin.abonnement_renouvele_jusquau')` n'existait dans AUCUN des
 *     deux fichiers de langue. Laravel affichait donc la cle en clair dans le
 *     toast de renouvellement : `admin.abonnement_renouvele_jusquau`.
 *
 *  2. `.env` portait `APP_FALLBACK_LOCALE=en`. Toute cle manquante en francais
 *     basculait donc silencieusement en anglais, sans aucun signe dans la
 *     page : un utilisateur francais ne pouvait pas savoir que la traduction
 *     lui manquait. Le repli est desormais `fr`, comme `config/app.php` le
 *     prevoyait par defaut.
 *
 * Ces tests verrouillent la parite fr/en ET l'absence de cle brute, pour que
 * le defaut ne puisse pas revenir par une nouvelle chaine ajoutee a la main.
 */
class TraductionsSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        $admin = Admin::create([
            'prenom'   => 'Super',
            'nom'      => 'Admin',
            'email'    => 'trad@test.cm',
            'password' => bcrypt('secret1234'),
        ]);
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'admin');
    }

    private function etablissement(): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => 'Ecole Traduction',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'trad-ecole@test.cm',
            'code_republique'       => 'CM',
        ]);
    }

    /** Le repli ne doit pas etre `en` : une cle manquante s'afficherait en anglais. */
    public function test_le_repli_de_langue_est_le_francais(): void
    {
        $this->assertSame('fr', config('app.fallback_locale'));
    }

    /**
     * Les deux fichiers de langue doivent exposer EXACTEMENT le meme jeu de
     * cles : c'est la condition pour qu'aucun utilisateur ne bascule dans
     * l'autre langue sur une chaine oubliee.
     */
    public function test_les_fichiers_fr_et_en_exposent_les_memes_cles(): void
    {
        $cles = function (string $langue): array {
            $fichier = lang_path($langue . '/admin.php');
            preg_match_all('/^\s*[\'"]([a-z0-9_]+)[\'"]\s*=>/m', file_get_contents($fichier), $m);
            return $m[1];
        };

        $fr = $cles('fr');
        $en = $cles('en');

        $this->assertNotEmpty($fr);

        $manqueEnFr = array_values(array_diff($en, $fr));
        $manqueEnEn = array_values(array_diff($fr, $en));

        $this->assertSame([], $manqueEnFr, 'Cles utilisees mais absentes de fr/admin.php : ' . implode(', ', $manqueEnFr));
        $this->assertSame([], $manqueEnEn, 'Cles utilisees mais absentes de en/admin.php : ' . implode(', ', $manqueEnEn));
    }

    /**
     * Aucune cle du back office ne doit s'afficher en clair. On balaie toutes
     * les chaines `__('x.y')` du code et des vues admin.
     */
    public function test_aucune_cle_de_traduction_ne_saffiche_en_clair(): void
    {
        $fichiers = [base_path('app/Http/Controllers/Admin')];
        $vues = glob(resource_path('views/admin/**/*.blade.php')) ?: [];
        $vues[] = resource_path('views/layouts/admin.blade.php');
        $fichiers = array_merge($fichiers, $vues);

        $manquantes = [];

        foreach ($fichiers as $chemin) {
            if (is_dir($chemin)) {
                $fichiersFils = [];
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($chemin));
                foreach ($it as $f) {
                    if ($f->isFile() && $f->getExtension() === 'php') {
                        $fichiersFils[] = $f->getPathname();
                    }
                }
                $chemins = $fichiersFils;
            } else {
                $chemins = [$chemin];
            }

            foreach ($chemins as $f) {
                $contenu = file_get_contents($f);
                preg_match_all('/__\(\s*[\'"]([a-z0-9_]+\.[a-z0-9_.]+)[\'"]/', $contenu, $m);

                foreach (array_unique($m[1]) as $cle) {
                    [$groupe, $reste] = explode('.', $cle, 2);
                    $trad = trans($groupe);
                    if (is_array($trad) && ! array_key_exists(explode('.', $reste)[0], $trad)) {
                        $manquantes[] = $cle . ' (' . basename($f) . ')';
                    }
                }
            }
        }

        $this->assertSame([], $manquantes, 'Cles de traduction introuvables : ' . implode(', ', $manquantes));
    }

    /**
     * Le cas reel signale : le toast de renouvellement affichait la cle brute
     * a la place du message.
     */
    public function test_le_message_de_renouvellement_est_traduit(): void
    {
        $message = __('admin.abonnement_renouvele_jusquau', [
            'date'    => '25/10/2026',
            'mois'    => 1,
            'montant' => '10 000',
        ]);

        $this->assertStringNotContainsString('abonnement_renouvele_jusquau', $message);
        $this->assertStringContainsString('25/10/2026', $message);
        $this->assertStringContainsString('10 000', $message);

        $this->assertSame('Subscription renewed until 25/10/2026 (1 months) - total 10 000 FCFA', trans('admin.abonnement_renouvele_jusquau', [
            'date' => '25/10/2026', 'mois' => 1, 'montant' => '10 000',
        ], 'en'));
    }

    /**
     * Les libelles de la liste des administrateurs etaient ecrits en dur en
     * francais dans la chaine HTML du DataTable (« Actif », « Jamais »,
     * title="Voir"), donc un utilisateur anglais lisait du francais.
     */
    public function test_les_libelles_de_la_liste_admins_sont_traduits(): void
    {
        $autre = Admin::create([
            'prenom' => 'Jean', 'nom' => 'Dupont',
            'email' => 'jean@test.cm', 'password' => bcrypt('secret1234'),
        ]);

        $json = $this->get(route('admin.admins.datatable'))->assertOk()->json();

        $ligne = collect($json['data'])->first(
            fn (array $r) => str_contains($r[0] ?? '', $autre->nom_complet)
        );
        $this->assertNotNull($ligne, 'La ligne du second admin est introuvable dans le DataTable.');

        // Colonnes : 0 identite, 1 role, 2 contact, 3 derniere connexion,
        // 4 statut, 5 actions.
        $this->assertStringContainsString(__('admin.actif'), $ligne[4]);
        $this->assertStringContainsString(__('admin.jamais_connecte'), $ligne[3]);

        // Les actions ne doivent garder ni francais en dur ni dessin inline.
        $this->assertStringContainsString('material-symbols-outlined', $ligne[5]);
        $this->assertStringNotContainsString('<svg', $ligne[5]);
        foreach (['voir', 'edit', 'suspendre', 'supprimer'] as $attendu) {
            $this->assertStringContainsString('title="' . e(__('admin.' . $attendu)) . '"', $ligne[5]);
        }
    }

    /**
     * Un utilisateur anglais ne doit pas lire de francais sur cette page, et
     * la bascule FR/EN doit se faire par le routeur de langue.
     */
    public function test_le_bascule_de_langue_sinverse_le_rendu(): void
    {
        $this->post(route('locale.switch'), ['locale' => 'en'])->assertRedirect();
        $this->assertSame('en', app()->getLocale());
        $this->assertSame('Edit', __('admin.edit'));
        $this->assertSame('View', __('admin.voir'));

        $this->post(route('locale.switch'), ['locale' => 'fr'])->assertRedirect();
        $this->assertSame('fr', app()->getLocale());
        $this->assertSame('Modifier', __('admin.edit'));
        $this->assertSame('Voir', __('admin.voir'));
    }
}
