<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Etablissement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rendu du back-office des abonnements.
 *
 * Verifie que la page dit la realite des dates :
 *  - une periode finie affiche « Expire », pas « 0 jours restants » ;
 *  - une periode en grace affiche l'echeance de grace ;
 *  - la duree reelle est affichee a cote des dates, ce qui rend visible
 *    immediatement le cas releve le 27/09/2026 (1 mois annonce, 13 mois
 *    enregistres) ;
 *  - le badge de statut suit l'etat calcule sur les dates.
 */
class AdminAbonnementAffichageTest extends TestCase
{
    use RefreshDatabase;

    private const ANNEE = '2026-2027';

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        $this->admin = Admin::create([
            'prenom'   => 'Super',
            'nom'      => 'Admin',
            'email'    => 'super@test.cm',
            'password' => bcrypt('secret1234'),
        ]);
        $this->admin->assignRole('super-admin');

        $this->actingAs($this->admin, 'admin');
    }

    private function etablissement(string $nom = 'Ecole Test'): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => $nom,
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'render@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => self::ANNEE,
        ]);
    }

    /** Lignes JSON du DataTables. */
    private function lignes(): array
    {
        $reponse = $this->getJson(route('admin.abonnements.datatable'));

        $reponse->assertOk();

        return json_decode($reponse->getContent(), true)['data'];
    }

    public function test_une_periode_finie_affiche_expire_et_non_zero_jours_restants(): void
    {
        $this->traiter('2026-09-27');

        $etablissement = $this->etablissement();
        $p = Abonnement::periode(Carbon::parse('2026-08-26'), 1);

        $abo = Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => $p['date_fin'],
            'grace_period_fin' => $p['grace_period_fin'],
            'statut'           => 'actif',
        ]);

        $ligne = $this->lignes()[0];

        // Le statut stocke est encore 'actif' : c'est bien l'etat calcule sur
        // les dates qui doit apparaitre.
        $this->assertSame('actif', $abo->statut);
        $this->assertStringContainsString('Expir', $ligne[2]);
        $this->assertStringNotContainsString('jours restants', $ligne[2]);
        $this->assertStringContainsString('25/09/2026', $ligne[2]);

        // Badge de statut : grace, et asterisk car non resynchronise.
        $this->assertStringContainsString('Grace', $ligne[3]);
        $this->assertStringContainsString('*', $ligne[3]);
    }

    public function test_une_periode_finie_hors_grace_affiche_expire_sans_grace(): void
    {
        $this->traiter('2026-10-05');

        $etablissement = $this->etablissement();
        $p = Abonnement::periode(Carbon::parse('2026-08-26'), 1);

        Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => $p['date_fin'],
            'grace_period_fin' => $p['grace_period_fin'],
            'statut'           => 'grace_period',
        ]);

        $ligne = $this->lignes()[0];

        $this->assertStringContainsString('Expir', $ligne[2]);
        $this->assertStringNotContainsString('Grâce', $ligne[2]);
        $this->assertStringNotContainsString('jours restants', $ligne[2]);
    }

    public function test_une_periode_en_cours_affiche_les_jours_restants(): void
    {
        $this->traiter('2026-09-01');

        $etablissement = $this->etablissement();
        $p = Abonnement::periode(Carbon::parse('2026-08-26'), 1);

        Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => $p['date_fin'],
            'grace_period_fin' => $p['grace_period_fin'],
            'statut'           => 'actif',
        ]);

        $ligne = $this->lignes()[0];

        $this->assertStringContainsString('24 jours restants', $ligne[2]);
        $this->assertStringNotContainsString('Expir', $ligne[2]);
        $this->assertStringNotContainsString('*', $ligne[3]);
    }

    /**
     * Le cas de reference : 1 mois annonce, 13 mois enregistres. Le badge de
     * duree doit permettre de le voir sans ouvrir la ligne.
     */
    public function test_la_duree_reelle_est_affichee_a_cote_des_dates(): void
    {
        $this->traiter('2026-09-27');

        $etablissement = $this->etablissement();

        Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => '2027-09-26',
            'grace_period_fin' => '2027-10-26',
            'statut'           => 'actif',
        ]);

        $ligne = $this->lignes()[0];

        $this->assertStringContainsString('ep-badge-duree', $ligne[2]);
        $this->assertStringContainsString('13 mois', $ligne[2]);
    }

    public function test_le_kpi_revenus_du_mois_ignore_les_autres_annees(): void
    {
        $this->traiter('2026-09-27');

        // Abonnement de septembre 2025 : ne doit pas compter dans le KPI 2026.
        $ancien = $this->etablissement('Ecole 2025');
        Abonnement::create([
            'etablissement_id' => $ancien->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2025-09-01',
            'date_fin'         => '2027-09-25',
            'grace_period_fin' => '2027-10-02',
            'statut'           => 'actif',
        ]);

        $actuel = $this->etablissement('Ecole 2026');
        Abonnement::create([
            'etablissement_id' => $actuel->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-09-01',
            'date_fin'         => '2026-09-30',
            'grace_period_fin' => '2026-10-07',
            'statut'           => 'actif',
        ]);

        $html = $this->get(route('admin.abonnements.index'))->assertOk()->getContent();

        // 10 000, pas 20 000 : whereMonth seul aurait additionne 2025 et 2026.
        $this->assertSame('10 000', $this->kpi($html, 'kpi-revenus'));
        $this->assertStringNotContainsString('20 000', $this->kpi($html, 'kpi-revenus'));

        $this->assertDatabaseHas('abonnements', ['etablissement_id' => $ancien->id]);
    }

    public function test_le_compteur_actifs_suit_les_dates_et_non_le_statut(): void
    {
        $this->traiter('2026-09-27');

        $etablissement = $this->etablissement();
        $p = Abonnement::periode(Carbon::parse('2026-08-26'), 1);

        // Statut 'actif' en base, periode terminee la veille.
        Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => $p['date_fin'],
            'grace_period_fin' => $p['grace_period_fin'],
            'statut'           => 'actif',
        ]);

        $html = $this->get(route('admin.abonnements.index'))->assertOk()->getContent();

        // Le compteur « actifs » ne doit pas englober une periode echue, meme si
        // la colonne statut dit encore « actif ».
        $this->assertSame('0', $this->kpi($html, 'kpi-actifs'));
        $this->assertSame('1', $this->kpi($html, 'kpi-grace'));
        $this->assertSame('1', $this->kpi($html, 'kpi-a-renouveler'));
        $this->assertSame('0', $this->kpi($html, 'kpi-expires'));
    }

    public function test_le_formulaire_propose_les_durees_et_le_recapitulatif(): void
    {
        $html = $this->get(route('admin.abonnements.index'))->assertOk()->getContent();

        foreach (['duree-mois-new', 'duree-mois-renew', 'duree-mois-edit'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html);
        }

        foreach ([1, 3, 6, 12] as $mois) {
            $this->assertStringContainsString('value="' . $mois . '"', $html);
        }

        $this->assertStringContainsString('periode-prevue-new', $html);
        $this->assertStringContainsString('function majPeriode', $html);
    }

    /**
     * Partitionnement en classes d'equivalence + valeurs limites sur la duree.
     *
     * Le controleur acceptait 1..24 pendant que le modele n'acceptait que
     * 1/3/6/12 : une duree comme 2 ou 13 passait la validation puis se
     * ramenait silencieusement a 1 mois. Aucun montant ne doit etre enregistre
     * pour une duree hors catalogue.
     */
    #[DataProvider('providerDureesHorsCatalogue')]
    public function test_une_duree_hors_catalogue_est_refusee_a_la_creation(int $duree): void
    {
        $etablissement = $this->etablissement('Ecole Duree ' . $duree);

        $this->from(route('admin.abonnements.index'))
            ->post(route('admin.abonnements.store'), [
                'etablissement_id' => $etablissement->id,
                'plan'             => 'standard',
                'date_debut'       => '2026-08-26',
                'duree_mois'       => $duree,
            ])
            ->assertRedirect(route('admin.abonnements.index'))
            ->assertSessionHasErrors('duree_mois');

        // Aucune ligne, et surtout aucun montant encaisse, ne doit etre cree.
        $this->assertDatabaseCount('abonnements', 0);
    }

    public static function providerDureesHorsCatalogue(): array
    {
        return [
            'duree nulle'    => [0],
            'duree negative' => [-3],
            'duree 2 mois'   => [2],
            'duree 13 mois'  => [13],
            'duree 24 mois'  => [24],
            'duree 999 mois' => [999],
        ];
    }

    #[DataProvider('providerDureesNonNumeriques')]
    public function test_une_duree_non_numerique_est_refusee(string $duree): void
    {
        $etablissement = $this->etablissement('Ecole Texte');

        $this->from(route('admin.abonnements.index'))
            ->post(route('admin.abonnements.store'), [
                'etablissement_id' => $etablissement->id,
                'plan'             => 'standard',
                'date_debut'       => '2026-08-26',
                'duree_mois'       => $duree,
            ])
            ->assertSessionHasErrors('duree_mois');

        $this->assertDatabaseCount('abonnements', 0);
    }

    public static function providerDureesNonNumeriques(): array
    {
        return [
            'mot'        => ['mensuel'],
            'decimal'    => ['1.5'],
            'liste'      => ['1,3,6'],
            'formule'    => ['3+4'],
        ];
    }

    #[DataProvider('providerDureesDuCatalogue')]
    public function test_une_duree_du_catalogue_cree_la_bonne_periode(int $duree, string $dateFin): void
    {
        $etablissement = $this->etablissement('Ecole Offre ' . $duree);

        $this->post(route('admin.abonnements.store'), [
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'date_debut'       => '2026-08-26',
            'duree_mois'       => $duree,
        ])->assertRedirect();

        $abonnement = Abonnement::where('etablissement_id', $etablissement->id)->sole();

        $this->assertSame('2026-08-26', $abonnement->date_debut->toDateString());
        $this->assertSame($dateFin, $abonnement->date_fin->toDateString());
        // La duree souscrite est relisible depuis les dates enregistrees.
        $this->assertSame($duree, $abonnement->dureeEnMois());
    }

    public static function providerDureesDuCatalogue(): array
    {
        return [
            '1 mois'  => [1,  '2026-09-25'],
            '3 mois'  => [3,  '2026-11-25'],
            '6 mois'  => [6,  '2027-02-25'],
            '12 mois' => [12, '2027-08-25'],
        ];
    }

    /**
     * Les modaux ne doivent pas deborder de l'ecran.
     *
     * Constate sur /admin-ep2026/abonnements : « Modifier » et « Renouveler »
     * depassaient la hauteur du viewport et affichaient deux barres de
     * defilement, celle du document en plus de celle du modal. Le fond
     * bougeait pendant qu'on remplissait le formulaire.
     *
     * Le correctif est structurel, donc on le verifie sur le HTML rendu :
     * overlay scrollable, panneau borne en hauteur, et surtout UNE SEULE zone
     * scrollable (le corps du formulaire) entre un entete et un pied figes.
     */
    public function test_les_modales_sont_bornees_en_hauteur_avec_un_seul_scroll(): void
    {
        $html = $this->get(route('admin.abonnements.index'))->assertOk()->getContent();

        foreach (['modal-new-abo', 'modal-renew-abo', 'modal-edit-abo', 'modal-delete-abo'] as $id) {
            $debut = strpos($html, 'id="' . $id . '"');
            $this->assertNotFalse($debut, $id . ' est absent de la page.');

            // L'overlay doit pouvoir defiler si le panneau depasse.
            $overlay = substr($html, $debut, strpos($html, '>', $debut) - $debut);
            $this->assertStringContainsString('overflow-y-auto', $overlay, $id . ' : overlay non scrollable.');

            $bloc = substr($html, $debut, strpos($html, 'id="modal-', $debut + 1) === false
                ? 6000
                : strpos($html, 'id="modal-', $debut + 1) - $debut);

            // Panneau borne, contenu en colonne flex.
            $this->assertStringContainsString('max-h-[calc(100vh-2rem)]', $bloc, $id . ' : panneau sans hauteur maximale.');
            $this->assertStringContainsString('flex-col', $bloc, $id . ' : panneau non organise en colonne.');

            // Une seule zone scrollable : le corps du formulaire.
            $this->assertSame(
                1,
                substr_count($bloc, 'overflow-y-auto flex-1'),
                $id . ' : le nombre de zones scrollables n\'est pas 1.'
            );

            // Entete et pied figes, donc toujours atteignables.
            $this->assertStringContainsString('shrink-0', $bloc, $id . ' : entete ou pied compressible.');
        }
    }

    /**
     * Le scroll de la page doit etre verrouille tant qu'un modal est ouvert,
     * et libere a la fermeture. C'est ce qui produisait la seconde barre.
     */
    public function test_le_modal_verrouille_le_scroll_de_la_page(): void
    {
        $html = $this->get(route('admin.abonnements.index'))->assertOk()->getContent();

        $this->assertStringContainsString("document.body.style.overflow = 'hidden'", $html);
        $this->assertStringContainsString("if (!ouvert) { document.body.style.overflow = ''; }", $html);
        $this->assertStringContainsString("e.key !== 'Escape'", $html);
    }

    /**
     * Non-regression : deux enregistrements du meme formulaire (double clic)
     * ne doivent pas laisser deux abonnements identiques.
     *
     * Concretement constate en base le 27/09/2026 : les abonnements #6 et #7
     * portaient la meme periode (26/08/2026 au 25/09/2026), pour le meme plan,
     * crees a 4 secondes d'ecart. Le KPI recettes comptait alors 20 000 FCFA
     * au lieu de 10 000 pour une seule periode reellement souscrite.
     *
     * Le second enregistrement est refuse, la transaction n'est donc pas
     * meme ouverte.
     */
    public function test_deux_enregistrements_identiques_donneent_un_seul_abonnement(): void
    {
        $etablissement = $this->etablissement('Ecole Double Clic');

        $payload = [
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'date_debut'       => '2026-08-26',
            'duree_mois'       => 1,
        ];

        $this->post(route('admin.abonnements.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->post(route('admin.abonnements.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, Abonnement::where('etablissement_id', $etablissement->id)->count());
        $this->assertSame(1, Abonnement::query()->sole()->dureeEnMois());
    }

    /** Une duree absente vaut 1 mois : c'est la regle metier du plan courant. */
    public function test_une_creation_sans_duree_applique_le_mois_par_defaut(): void
    {
        $etablissement = $this->etablissement('Ecole Sans Duree');

        $this->post(route('admin.abonnements.store'), [
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'date_debut'       => '2026-08-26',
        ])->assertRedirect();

        $abonnement = Abonnement::where('etablissement_id', $etablissement->id)->sole();

        $this->assertSame('2026-09-25', $abonnement->date_fin->toDateString());
        $this->assertSame(1, $abonnement->dureeEnMois());
    }

    /**
     * Non-regression : une ligne historique de 13 mois ne doit jamais perdre sa
     * periode. Le select ne proposait que 1/3/6/12, donc il renvoyait une
     * valeur vide et l'enregistrement recalculait une duree de 1 mois,
     * reecrivant l'echeance et le montant deja encaisses, sans aucun message.
     */
    public function test_enregistrer_une_periode_hors_catalogue_sans_toucher_la_duree_la_preserve(): void
    {
        $etablissement = $this->etablissement('Ecole Legacy');
        $abonnement = Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => '2027-09-26',
            'grace_period_fin' => '2027-10-03',
            'statut'           => 'actif',
        ]);

        $this->assertSame(13, $abonnement->dureeEnMois());

        // Cas 1 : le select ne renvoie rien (aucune duree choisie).
        $this->patch(route('admin.abonnements.update', $abonnement), [
            'plan'         => 'standard',
            'duree_mois'   => '',
            'notes'        => 'Relance commerciale',
        ])->assertRedirect();

        $this->assertSame('2027-09-26', $abonnement->fresh()->date_fin->toDateString());
        $this->assertSame(13, $abonnement->fresh()->dureeEnMois());

        // Cas 2 : la duree hors offre est explicitement renvoyee (option
        // « hors offre » ajoutee par le formulaire) : elle doit etre acceptee.
        $this->patch(route('admin.abonnements.update', $abonnement), [
            'plan'       => 'standard',
            'duree_mois' => 13,
        ])->assertSessionHasNoErrors();

        $this->assertSame('2027-09-26', $abonnement->fresh()->date_fin->toDateString());
        $this->assertSame(13, $abonnement->fresh()->dureeEnMois());
    }

    /** Choisir explicitement une duree du catalogue doit bien raccourcir. */
    public function test_choisir_une_duree_du_catalogue_recalcule_la_periode(): void
    {
        $etablissement = $this->etablissement('Ecole Raccourci');
        $abonnement = Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => '2027-09-26',
            'grace_period_fin' => '2027-10-03',
            'statut'           => 'actif',
        ]);

        $this->patch(route('admin.abonnements.update', $abonnement), [
            'plan'       => 'standard',
            'duree_mois' => 3,
        ])->assertSessionHasNoErrors();

        $frais = $abonnement->fresh();
        $this->assertSame('2026-11-25', $frais->date_fin->toDateString());
        $this->assertSame('2026-12-02', $frais->grace_period_fin->toDateString());
        $this->assertSame(3, $frais->dureeEnMois());
    }

    /**
     * Le montant total doit correspondre a l'argent reellement encaisse :
     * prix du plan multiplie par la duree souscrite.
     */
    #[DataProvider('providerMontantsTotaux')]
    public function test_le_montant_total_depend_de_la_duree(int $duree, int $attendu): void
    {
        $etablissement = $this->etablissement('Ecole Montant');
        $abonnement = Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-08-26',
            'date_fin'         => Abonnement::dateFinPour(Carbon::parse('2026-08-26'), $duree),
            'grace_period_fin' => Abonnement::gracePeriodPour(
                Abonnement::dateFinPour(Carbon::parse('2026-08-26'), $duree)
            ),
            'statut'           => 'actif',
        ]);

        $this->assertSame($duree, $abonnement->dureeEnMois());
        $this->assertSame($attendu, $abonnement->montantTotal());
    }

    public static function providerMontantsTotaux(): array
    {
        return [
            'standard 1 mois'   => [1,  10000],
            'standard 3 mois'   => [3,  30000],
            'standard 6 mois'   => [6,  60000],
            'standard 12 mois'  => [12, 120000],
        ];
    }

    public function test_le_montant_total_est_affiche_dans_le_back_office(): void
    {
        $this->traiter('2026-09-27');

        $etablissement = $this->etablissement('Ecole 12 Mois');
        $p = Abonnement::periode(Carbon::parse('2026-09-01'), 12);

        Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'date_debut'       => '2026-09-01',
            'date_fin'         => $p['date_fin'],
            'grace_period_fin' => $p['grace_period_fin'],
            'statut'           => 'actif',
        ]);

        $html = $this->get(route('admin.abonnements.index'))->assertOk()->getContent();

        // Le recapitulatif du formulaire propose le montant a encaisser.
        $this->assertStringContainsString('montant-prevu-new', $html);
        $this->assertStringContainsString('montant-prevu-renew', $html);
        $this->assertStringContainsString('montant-prevu-edit', $html);
        $this->assertStringContainsString('function majMontant', $html);

        // Le KPI « encaisses » additionne les totaux, pas les prix unitaires.
        $this->assertSame('120 000', $this->kpi($html, 'kpi-revenus'));
    }

    public function test_le_kpi_encaisse_les_montants_totaux_et_non_les_prix_unitaires(): void
    {
        $this->traiter('2026-09-27');

        $etablissement = $this->etablissement('Ecole Recette');

        // 3 x 10 000 + 12 x 10 000 = 150 000, et non 20 000.
        foreach (['2026-09-01' => 3, '2026-09-05' => 12] as $debut => $mois) {
            $p = Abonnement::periode(Carbon::parse($debut), $mois);

            Abonnement::create([
                'etablissement_id' => $etablissement->id,
                'plan'             => 'standard',
                'montant_mensuel'  => 10000,
                'date_debut'       => $debut,
                'date_fin'         => $p['date_fin'],
                'grace_period_fin' => $p['grace_period_fin'],
                'statut'           => 'actif',
            ]);
        }

        $html = $this->get(route('admin.abonnements.index'))->assertOk()->getContent();

        $this->assertSame('150 000', $this->kpi($html, 'kpi-revenus'));
    }

    public function test_le_toast_de_grace_est_bien_rendu_dans_le_layout(): void
    {
        $html = file_get_contents(resource_path('views/layouts/etablissement.blade.php'));

        $this->assertStringContainsString("session('warning_abonnement')", $html);

        $toast = $this->extraitToast($html);
        $this->assertStringContainsString('material-symbols-outlined', $toast);
        // Material Symbols, pas un dessin blanc inline dans le toast.
        $this->assertStringNotContainsString('<svg', $toast);
    }

    /** Valeur affichee dans une tuile KPI, lue par son identifiant stable. */
    private function kpi(string $html, string $id): string
    {
        $motif = '/id="' . preg_quote($id, '/') . '"[^>]*>(.*?)</s';

        $this->assertSame(1, preg_match($motif, $html, $m), "Tuile KPI #{$id} introuvable.");

        return trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
    }

    /** Bloc du toast de grace seul, sans les blocs voisins. */
    private function extraitToast(string $html): string
    {
        $debut = strpos($html, "session('warning_abonnement')");
        $this->assertNotFalse($debut, 'Toast de grace absent du layout.');

        $ouvert = strpos($html, '<div class="toast', $debut);
        $ferme  = strpos($html, '</div>', $ouvert ?: 0);

        return $ouvert === false || $ferme === false
            ? ''
            : substr($html, $ouvert, $ferme - $ouvert);
    }

    private function traiter(string $jour): void
    {
        Carbon::setTestNow(Carbon::parse($jour));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        AuditLog::query()->delete();

        parent::tearDown();
    }
}
