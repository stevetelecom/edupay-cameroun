<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Abonnement extends Model
{
    protected $fillable = [
        'etablissement_id', 'plan', 'montant_mensuel',
        'date_debut', 'date_fin', 'grace_period_fin',
        'statut', 'reference_paiement', 'notes',
        'active_par', 'active_at',
    ];

    protected $casts = [
        'etablissement_id' => 'integer',
        'montant_mensuel'  => 'integer',
        'date_debut'       => 'date',
        'date_fin'         => 'date',
        'grace_period_fin' => 'date',
        'active_at'        => 'datetime',
    ];

    // Plans disponibles avec leurs caractéristiques
    const PLANS = [
        'basique' => [
            'nom'            => 'Basique',
            'montant'        => 5000,
            'max_apprenants' => 100,
            'sms_mensuel'    => 10,
            'multi_sites'    => false,
            'exports_cobac'  => false,
            'couleur'        => '#0D9E75',
        ],
        'standard' => [
            'nom'            => 'Standard',
            'montant'        => 10000,
            'max_apprenants' => 300,
            'sms_mensuel'    => -1, // illimité
            'multi_sites'    => true,
            'exports_cobac'  => false,
            'couleur'        => '#185FA5',
        ],
        'premium' => [
            'nom'            => 'Premium',
            'montant'        => 20000,
            'max_apprenants' => -1, // illimité
            'sms_mensuel'    => -1,
            'multi_sites'    => true,
            'exports_cobac'  => true,
            'couleur'        => '#E8A020',
        ],
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function activePar()
    {
        return $this->belongsTo(Admin::class, 'active_par');
    }

    /**
     * Abonnement mensuel : c'est la SEULE règle de durée de l'application.
     *
     * Elle etait ecrite trois fois en dur dans Admin\AbonnementController
     * (store, update, renouveler) sans contre-verification : rien n'empechait
     * d'enregistrer une periode de 13 mois, et le back office affichait alors
     * « 364 jours restants » pour un abonnement qui devait durer 1 mois
     * (constate en base le 27/09/2026 sur les abonnements #6 et #7, inseres
     * hors application). Toute duree passe donc par ici.
     */
    public const DUREE_PAR_DEFAUT_MOIS = 1;

    /** Durees proposables dans le back office. */
    public const DUREES_MOIS = [1, 3, 6, 12];

    /** Jours deTolerance apres la fin de periode, avant blocage. */
    public const JOURS_DE_GRACE = 7;

    /**
     * Fin de periode pour une duree donnee : debut + N mois − 1 jour.
     *
     * Le −1 jour rend la periode lisible : un abonnement du 26/08 au 25/09
     * fait exactement un mois et ne se chevauche pas avec le suivant.
     */
    public static function dateFinPour(Carbon $debut, ?int $mois = null): Carbon
    {
        $mois = self::normaliserDuree($mois);

        // addMonthsNoOverflow() et non addMonths() : PHP deborde le jour au
        // mois suivant quand le jour n'existe pas. Un abonnement du 31 janvier
        // + 1 mois tombait le 3 mars, donc au 2 mars apres le -1 jour, soit
        // 31 jours au lieu d'un mois. Pire, dureeEnMois() relisait « 2 mois »
        // et le back office affichait 2 mois pour un abonnement mensuel.
        return $debut->copy()->addMonthsNoOverflow($mois)->subDay();
    }

    public static function gracePeriodPour(Carbon $dateFin): Carbon
    {
        return $dateFin->copy()->addDays(self::JOURS_DE_GRACE);
    }

    /** Calcule les trois dates d'un abonnement de facon coherente. */
    public static function periode(Carbon $debut, ?int $mois = null): array
    {
        $dateFin = self::dateFinPour($debut, $mois);

        return [
            'date_fin'         => $dateFin,
            'grace_period_fin' => self::gracePeriodPour($dateFin),
            'duree_mois'       => self::normaliserDuree($mois),
        ];
    }

    public static function normaliserDuree(?int $mois): int
    {
        $mois = (int) $mois;

        return in_array($mois, self::DUREES_MOIS, true) ? $mois : self::DUREE_PAR_DEFAUT_MOIS;
    }

    /**
     * Duree reellement souscrite, deduite des dates (pas de colonne en base).
     * 26/08 -> 25/09 = 1 mois ; 26/08 -> 25/08 de l'an suivant = 12 mois.
     */
    public function dureeEnMois(): int
    {
        if (! $this->date_debut || ! $this->date_fin) {
            return 0;
        }

        // Inverse exact de dateFinPour() : date_fin = date_debut + N mois - 1
        // jour, donc N est le plus grand entier tel que
        // date_debut + N mois <= date_fin + 1 jour.
        //
        // diffInMonths() ne peut PAS servir ici : Carbon compte des mois
        // calendaires complets et renvoie 0 pour 31/01 -> 28/02 (et pour
        // 31/05 -> 30/06), donc un abonnement d'un mois y ressortait a 0 puis
        // etait force a 1 par le max(). Avec +1 il renvoyait 14 mois pour une
        // periode de 13 mois. On incremente donc jusqu'a depasser la borne.
        $borne   = $this->date_fin->copy()->addDay();
        $debut   = $this->date_debut->copy();
        $duree   = 0;

        while ($debut->copy()->addMonthsNoOverflow($duree + 1)->lte($borne)) {
            $duree++;

            if ($duree > 1200) { // garde-fou : 100 ans
                break;
            }
        }

        return max(1, $duree);
    }

    /**
     * Periode echue ? Indique par les DATES, pas par le statut stocke.
     */
    public function estExpire(): bool
    {
        return $this->date_fin !== null && Carbon::today()->gt($this->date_fin);
    }

    /**
     * Etat reel de l'abonnement, deduit des dates.
     *
     * Le statut stocke en base ne peut pas servir de reference : il n'etait
     * recalcule que par le middleware CheckAbonnement, c'est-a-dire au
     * passage d'un eleve sur une page. Tant qu'aucun utilisateur de
     * l'etablissement ne se connecte, un abonnement dont la periode est
     * finie continuait d'afficher « actif » dans le back office (constate le
     * 27/09/2026 : les abonnements #6 et #7, fin de periode le 25/09).
     */
    public function etat(): string
    {
        if (! $this->date_fin) {
            return 'expire';
        }

        if (! $this->estExpire()) {
            return 'actif';
        }

        return $this->enGracePeriod() ? 'grace_period' : 'expire';
    }

    /** Le statut enregistre est-il en retard sur les dates ? */
    public function statutDesynchronise(): bool
    {
        return $this->statut !== $this->etat();
    }

    /** L'etablissement a-t-il encore acces (periode valide ou en grace) ? */
    public function estActif(): bool
    {
        return in_array($this->etat(), ['actif', 'grace_period']);
    }

    public function joursRestants(): int
    {
        if (! $this->date_fin) {
            return 0;
        }

        return max(0, (int) Carbon::today()->diffInDays($this->date_fin, false));
    }

    public function enGracePeriod(): bool
    {
        return $this->estExpire()
            && $this->grace_period_fin !== null
            && Carbon::today()->lte($this->grace_period_fin);
    }

    /** Periode attendue d'apres la duree : sert de garde-fou a la saisie. */
    public function periodeAttendue(): string
    {
        return $this->date_debut?->format('d/m/Y') . ' au ' . self::dateFinPour($this->date_debut, $this->dureeEnMois())->format('d/m/Y');
    }

    public static function montantPlan(string $plan): int
    {
        return self::PLANS[$plan]['montant'] ?? 5000;
    }

    /**
     * Montant total du au titre de la periode souscrite.
     *
     * `montant_mensuel` est un prix unitaire : l'administrateur encaisse
     * 10 000 FCFA par mois, donc 30 000 pour 3 mois et 120 000 pour 12 mois.
     * Sans ce calcul, le back office n'affichait que le prix du plan et le
     * montant reellement paye restait invisible.
     *
     * La duree relue vient des dates (dureeEnMois), donc le total est toujours
     * coherent avec la periode affichee, y compris pour les lignes
     * historiques hors catalogue.
     */
    public function montantTotal(): int
    {
        return (int) $this->montant_mensuel * max(1, $this->dureeEnMois());
    }

    /**
     * Montant total pour une duree donnee, avant enregistrement.
     * Utilise par les formulaires pour afficher le montant a encaisser.
     */
    public static function montantTotalPour(string $plan, ?int $mois = null): int
    {
        return self::montantPlan($plan) * self::normaliserDuree($mois);
    }
}
