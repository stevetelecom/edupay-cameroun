<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Etablissement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cycle de vie d'un abonnement mensuel.
 *
 * Contexte (constate le 27/09/2026) : les abonnements #6 et #7 de
 * l'etablissement 1 portaient `date_fin = 2027-09-26` pour un `date_debut`
 * au 26/08/2026, soit 13 mois au lieu d'un. Le back office affichait
 * « 364 jours restants ». La regle mensuelle etait ecrite en dur trois fois
 * dans le controleur, sans controle, et rien n'empechait d'enregistrer une
 * periode arbitraire.
 *
 * Ces tests verrouillent :
 *  - la regle « 1 mois par defaut » et les durees 1/3/6/12 ;
 *  - que la PERIODE DE GRACE laisse tout passer (rien n'est bloque) ;
 *  - qu'au-dela, seules 4 routes survivent et le reste part sur la page
 *    d'abonnement ;
 *  - que l'etat derive des DATES, jamais du statut stocke.
 */
class AbonnementPeriodeTest extends TestCase
{
    use RefreshDatabase;

    private const ANNEE = '2026-2027';

    private Etablissement $etablissement;

    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'directeur']);

        $this->etablissement = Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => 'Ecole Test',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'ecole-test@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => self::ANNEE,
        ]);

        $this->directeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->directeur->assignRole('directeur');

        // Les tests de periode exercent reellement le middleware
        // CheckAbonnement : sans session, toutes les routes repondaient 302
        // vers la connexion et le test ne verifiait rien.
        $this->actingAs($this->directeur);
    }

    private function creerAbonnement(array $attributs = []): Abonnement
    {
        return Abonnement::create(array_merge([
            'etablissement_id' => $this->etablissement->id,
            'plan'             => 'standard',
            'montant_mensuel'  => 10000,
            'statut'           => 'actif',
        ], $attributs));
    }

    /** Periodes : mensuel, et cas releves en base. */
    public static function providerDurees(): array
    {
        return [
            '1 mois  (defaut)'   => [null,        '2026-09-25'],
            '1 mois  (explicite)'=> [1,          '2026-09-25'],
            '3 mois'             => [3,          '2026-11-25'],
            '6 mois'             => [6,          '2027-02-25'],
            '12 mois'            => [12,         '2027-08-25'],
        ];
    }

    #[DataProvider('providerDurees')]
    public function test_la_duree_choisie_determine_la_date_de_fin(?int $duree, string $attendu): void
    {
        $this->assertSame(
            $attendu,
            Abonnement::dateFinPour(Carbon::parse('2026-08-26'), $duree)->toDateString()
        );
    }

    #[DataProvider('providerDurees')]
    public function test_la_periode_complete_est_coherente(?int $duree, string $attendu): void
    {
        $periode = Abonnement::periode(Carbon::parse('2026-08-26'), $duree);

        $this->assertSame($attendu, $periode['date_fin']->toDateString());

        // La grâce suit toujours la fin de période de 7 jours, quelle que
        // soit la durée souscrite.
        $this->assertSame(
            Carbon::parse($attendu)->addDays(7)->toDateString(),
            $periode['grace_period_fin']->toDateString()
        );

        $this->assertSame(Abonnement::normaliserDuree($duree), $periode['duree_mois']);
    }

    public function test_la_duree_par_defaut_est_un_mois_et_non_un_an(): void
    {
        $debut = Carbon::parse('2026-08-26');
        $fin   = Abonnement::dateFinPour($debut);

        $this->assertSame(1, Abonnement::DUREE_PAR_DEFAUT_MOIS);
        $this->assertSame('2026-09-25', $fin->toDateString());

        // Le piege du 27/09/2026 : 396 jours au lieu d'un mois.
        $this->assertSame(30, (int) $debut->diffInDays($fin));
        $this->assertNotSame('2027-08-25', $fin->toDateString());
    }

    public function test_une_duree_hors_barre_est_ramenee_a_un_mois(): void
    {
        $this->assertSame(1, Abonnement::normaliserDuree(13));
        $this->assertSame(1, Abonnement::normaliserDuree(0));
        $this->assertSame(1, Abonnement::normaliserDuree(null));
        $this->assertSame(6,  Abonnement::normaliserDuree(6));
    }

    /**
     * Fins de mois : PHP deborde le jour au mois suivant quand il n'existe
     * pas. Avec addMonths(), un abonnement du 31 janvier + 1 mois finissait le
     * 2 mars (31 jours) et dureeEnMois() relisait « 2 mois » : le back office
     * affichait 2 mois pour un abonnement mensuel.
     */
    public static function providerFinsDeMois(): array
    {
        return [
            '31 janvier,  1 mois'  => ['2026-01-31', 1,  '2026-02-27'],
            '31 janvier,  3 mois'  => ['2026-01-31', 3,  '2026-04-29'],
            '31 janvier, 12 mois'  => ['2026-01-31', 12, '2027-01-30'],
            '31 mars,     1 mois'  => ['2026-03-31', 1,  '2026-04-29'],
            '31 mars,     3 mois'  => ['2026-03-31', 3,  '2026-06-29'],
            '28 fevrier,  1 mois'  => ['2026-02-28', 1,  '2026-03-27'],
            '28 fevrier, 12 mois'  => ['2026-02-28', 12, '2027-02-27'],
            '29 fevrier bissextile' => ['2024-02-29', 1,  '2024-03-28'],
            '29 fevrier, 12 mois'  => ['2024-02-29', 12, '2025-02-27'],
            '31 mai,      1 mois'  => ['2026-05-31', 1,  '2026-06-29'],
            '31 mai,      6 mois'  => ['2026-05-31', 6,  '2026-11-29'],
            '31 decembre, 6 mois'  => ['2026-12-31', 6,  '2027-06-29'],
            '31 aout,     1 mois'  => ['2026-08-31', 1,  '2026-09-29'],
            '31 aout,    12 mois'  => ['2026-08-31', 12, '2027-08-30'],
            '1er janvier,12 mois'  => ['2026-01-01', 12, '2026-12-31'],
            '30 avril,    3 mois'  => ['2026-04-30', 3,  '2026-07-29'],
        ];
    }

    #[DataProvider('providerFinsDeMois')]
    public function test_les_fins_de_mois_ne_debordent_pas(string $debut, int $mois, string $attendu): void
    {
        $dateFin = Abonnement::dateFinPour(Carbon::parse($debut), $mois);

        $this->assertSame($attendu, $dateFin->toDateString());

        // La duree relue depuis les dates doit rendre exactement la duree
        // souscrite, sinon le badge du back office ment sur le contrat.
        $abonnement = $this->creerAbonnement($this->periode($debut, $mois));

        $this->assertSame($mois, $abonnement->dureeEnMois());
        $this->assertIsInt($abonnement->dureeEnMois());
    }

    public function test_la_duree_lue_est_un_entier_et_non_un_flottant(): void
    {
        // precision ». Le nombre de mois affiche ne doit jamais etre 1.967...
        $duree = $this->creerAbonnement($this->periode('2026-08-26', 3))->dureeEnMois();

        $this->assertIsInt($duree);
        $this->assertSame(3, $duree);
    }

    public function test_la_duree_reelle_est_reluite_a_partir_des_dates(): void
    {
        $this->assertSame(1,  $this->creerAbonnement($this->periode('2026-08-26', 1))->dureeEnMois());
        $this->assertSame(6,  $this->creerAbonnement($this->periode('2026-08-26', 6))->dureeEnMois());
        $this->assertSame(12, $this->creerAbonnement($this->periode('2026-08-26', 12))->dureeEnMois());
    }

    /**
     * Donnees de production #6 / #7 : periodes de 13 mois saisies « mensuelles »
     * hors application. Le back office doit afficher 13 mois, pas 14 : c'est
     * exactement ce desaccord qui a rendu le defaut invisible.
     */
    public function test_une_periode_hors_offre_est_reluite_sans_arrondi_au_mois_supplementaire(): void
    {
        // 13 mois n'existe pas dans le catalogue : Abonnement::periode()
        // refuse voluntarily cette duree. On saisit donc les dates reelles,
        // comme les lignes #6 / #7 l'ont ete hors application.
        $abonnement = $this->creerAbonnement([
            'date_debut'       => '2026-08-26',
            'date_fin'         => '2027-09-26',
            'grace_period_fin' => '2027-10-03',
        ]);

        $this->assertSame(13, $abonnement->dureeEnMois());
    }

    private function periode(string $debut, int $mois): array
    {
        $p = Abonnement::periode(Carbon::parse($debut), $mois);

        return [
            'date_debut'       => $debut,
            'date_fin'         => $p['date_fin']->toDateString(),
            'grace_period_fin' => $p['grace_period_fin']->toDateString(),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Période de grâce : TOUT reste autorisé
    // ══════════════════════════════════════════════════════════════

    public function test_la_periode_de_grace_laisse_toutes_les_pages_accessibles(): void
    {
        // Période finie il y a 2 jours, grâce encore ouverte dans 5 jours.
        $this->traiter(['now' => '2026-09-27']);

        $this->creerAbonnement($this->periode('2026-08-26', 1));

        $enregistre = Abonnement::where('etablissement_id', $this->etablissement->id)->firstOrFail();
        $this->assertSame('2026-09-25', $enregistre->date_fin->toDateString());
        $this->assertSame('2026-10-02', $enregistre->grace_period_fin->toDateString());

        // Toutes les routes métier doivent répondre 200 : rien n'est bloqué
        // pendant la grâce.
        foreach (['etablissement.dashboard', 'etablissement.apprenants.index', 'etablissement.frais.index',
                  'etablissement.paiements.index', 'etablissement.impayes.index', 'etablissement.rapports.index',
                  'etablissement.parametres.index', 'etablissement.sites.index', 'etablissement.aide.index'] as $route) {
            $this->get(route($route))->assertOk();
        }

        // Et le statut passe en grâce, avec l'alerte destinée à l'écran.
        $this->assertDatabaseHas('abonnements', [
            'etablissement_id' => $this->etablissement->id,
            'statut'           => 'grace_period',
        ]);

        $this->get(route('etablissement.dashboard'))
            ->assertSessionHas('warning_abonnement');
    }

    public function test_au_dela_de_la_grace_seules_quatre_routes_survivent(): void
    {
        // Grâce close le 02/10, on est le 03/10.
        $this->traiter(['now' => '2026-10-03']);

        $this->creerAbonnement($this->periode('2026-08-26', 1));

        // Autorisées.
        $this->get(route('etablissement.dashboard'))->assertOk();
        $this->get(route('etablissement.abonnement.requis'))->assertOk();
        $this->get(route('etablissement.profil.index'))->assertOk();

        // Bloquées : chaque lien de la sidebar repart sur la page d'abonnement.
        foreach (['etablissement.apprenants.index', 'etablissement.frais.index',
                  'etablissement.paiements.index', 'etablissement.impayes.index',
                  'etablissement.rapports.index', 'etablissement.parametres.index',
                  'etablissement.sites.index', 'etablissement.utilisateurs.index',
                  'etablissement.remboursements.index'] as $route) {
            $this->get(route($route))
                ->assertRedirect(route('etablissement.abonnement.requis'));
        }

        $this->assertDatabaseHas('abonnements', [
            'etablissement_id' => $this->etablissement->id,
            'statut'           => 'expire',
        ]);
    }

    public function test_au_dela_de_la_grace_le_plan_de_l_etablissement_repasse_a_aucun(): void
    {
        $this->traiter(['now' => '2026-10-03']);

        $this->creerAbonnement($this->periode('2026-08-26', 1));
        $this->etablissement->update([
            'plan_abonnement'      => 'standard',
            'abonnement_expire_le' => '2026-09-25',
        ]);

        $this->get(route('etablissement.dashboard'))->assertOk();

        // Cette colonne n'etait pas dans $fillable : l'update() echouait
        // silencieusement et l'ecole restait affichee « plan = standard »
        // alors qu'elle etait bloquee.
        $this->assertDatabaseHas('etablissements', [
            'id'                 => $this->etablissement->id,
            'plan_abonnement'    => 'aucun',
        ]);
    }

    public function test_un_etablissement_sans_abonnement_est_bloque_hors_dashboard(): void
    {
        $this->get(route('etablissement.dashboard'))->assertOk();
        $this->get(route('etablissement.abonnement.requis'))->assertOk();
        $this->get(route('etablissement.apprenants.index'))
            ->assertRedirect(route('etablissement.abonnement.requis'));
    }

    public function test_une_periode_valide_ne_bloque_rien(): void
    {
        $this->traiter(['now' => '2026-09-01']);

        $this->creerAbonnement($this->periode('2026-08-26', 1));

        foreach (['etablissement.dashboard', 'etablissement.apprenants.index',
                  'etablissement.frais.index', 'etablissement.paiements.index'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->assertDatabaseHas('abonnements', [
            'etablissement_id' => $this->etablissement->id,
            'statut'           => 'actif',
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // L'état dérive des DATES, pas du statut stocké
    // ══════════════════════════════════════════════════════════════

    public function test_etat_calcule_sur_les_dates_et_non_sur_le_statut_stocke(): void
    {
        $this->traiter(['now' => '2026-09-27']);

        // Statut 'actif' alors que la période est finie : c'est exactement
        // l'etat laisse en base depuis le 26/09.
        $expire = $this->creerAbonnement($this->periode('2026-08-26', 1));

        $this->assertSame('actif', $expire->statut);
        $this->assertTrue($expire->statutDesynchronise());
        $this->assertSame('grace_period', $expire->etat());
        $this->assertTrue($expire->enGracePeriod());
        $this->assertTrue($expire->estActif());
        $this->assertSame(0, $expire->joursRestants());
    }

    public function test_un_statut_expire_avec_des_dates_valides_redonne_acces(): void
    {
        $this->traiter(['now' => '2026-09-01']);

        // Le middleware avait passe cette ligne en 'expire' lors d'une visite
        // anterieure ; apres renouvellement des dates, elle redevait active.
        $abonnement = $this->creerAbonnement($this->periode('2026-08-26', 1));
        $abonnement->update(['statut' => 'expire']);

        $this->assertSame('expire', $abonnement->statut);
        $this->assertSame('actif', $abonnement->etat());
        $this->assertTrue($abonnement->statutDesynchronise());
        $this->assertTrue($abonnement->estActif());

        $this->get(route('etablissement.apprenants.index'))->assertOk();
    }

    public function test_les_dates_nulles_ne_font_pas_planter_le_modele(): void
    {
        $abonnement = new Abonnement(['statut' => 'actif']);

        $this->assertSame(0, $abonnement->joursRestants());
        $this->assertFalse($abonnement->estExpire());
        $this->assertFalse($abonnement->enGracePeriod());
        $this->assertFalse($abonnement->estActif());
        $this->assertSame(0, $abonnement->dureeEnMois());
        $this->assertSame('expire', $abonnement->etat());
    }

    // ══════════════════════════════════════════════════════════════
    // Synchronisation
    // ══════════════════════════════════════════════════════════════

    public function test_la_commande_resynchronise_le_statut_et_idempotente(): void
    {
        $this->traiter(['now' => '2026-09-27']);

        $this->creerAbonnement($this->periode('2026-08-26', 1));
        $this->etablissement->update(['plan_abonnement' => 'aucun', 'abonnement_expire_le' => null]);

        $this->artisan('abonnements:synchroniser')
            ->expectsOutputToContain('1 abonnement(s) resynchronisé(s)')
            ->assertSuccessful();

        $this->assertDatabaseHas('abonnements', [
            'etablissement_id' => $this->etablissement->id,
            'statut'           => 'grace_period',
        ]);
        $this->assertDatabaseHas('etablissements', [
            'id'              => $this->etablissement->id,
            'plan_abonnement' => 'standard',
        ]);

        // Deuxieme passage : plus rien a corriger.
        $this->artisan('abonnements:synchroniser')
            ->expectsOutputToContain('0 abonnement(s) resynchronisé(s)')
            ->assertSuccessful();
    }

    public function test_la_commande_signale_les_periodes_identiques_sans_supprimer(): void
    {
        $this->traiter(['now' => '2026-09-01']);

        $periode = $this->periode('2026-08-26', 1);
        $this->creerAbonnement($periode);
        $this->creerAbonnement($periode);

        $this->artisan('abonnements:synchroniser')
            ->expectsOutputToContain('abonnements en cours qui se recouvrent')
            ->expectsOutputToContain('Aucune suppression')
            ->assertSuccessful();

        // Aucune suppression : ce sont des donnees financieres.
        $this->assertSame(2, Abonnement::where('etablissement_id', $this->etablissement->id)->count());
    }

    public function test_la_commande_signale_un_recouvrement_partiel(): void
    {
        $this->traiter(['now' => '2026-09-01']);

        $this->creerAbonnement($this->periode('2026-08-26', 1));
        $this->creerAbonnement($this->periode('2026-09-10', 1));

        $this->artisan('abonnements:synchroniser')
            ->expectsOutputToContain('recouvrement')
            ->assertSuccessful();
    }

    public function test_la_commande_ne_touche_a_aucune_date(): void
    {
        $this->traiter(['now' => '2026-10-03']);

        $abonnement = $this->creerAbonnement($this->periode('2026-08-26', 1));

        $this->artisan('abonnements:synchroniser')->assertSuccessful();

        $abonnement->refresh();
        $this->assertSame('2026-08-26', $abonnement->date_debut->toDateString());
        $this->assertSame('2026-09-25', $abonnement->date_fin->toDateString());
        $this->assertSame('2026-10-02', $abonnement->grace_period_fin->toDateString());
        $this->assertSame('expire', $abonnement->statut);
    }

    public function test_le_middleware_privilegie_la_periode_la_plus_recente(): void
    {
        $this->traiter(['now' => '2026-09-01']);

        // Cas des doublons #6 / #7 : memes dates, departage non deterministe
        // auparavant (created_at identique).
        $this->creerAbonnement($this->periode('2026-08-26', 1));
        $second = $this->creerAbonnement($this->periode('2026-09-15', 1));

        $this->assertSame(
            $second->id,
            $this->etablissement->fresh()->abonnementCourant()?->id
        );
    }

    private function traiter(array $options): void
    {
        Carbon::setTestNow(Carbon::parse($options['now']));
    }
}
