<?php

namespace App\Support;

use App\Models\Etablissement;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Source de verite unique pour l'annee scolaire (audit A).
 *
 * AVANT : '2025-2026' etait ecrit en dur dans 13 endroits (dashboard, impayes,
 * relances, rapport financier, sidebar, ...) et '2025-2026'/'2026-2027' dans
 * les <select> des formulaires de frais. Aucune notion d'annee active : au
 * 26/09/2026 les chiffres etaient vides/perimes.
 *
 * Resolution (dans cet ordre) :
 *   1. $etablissement->annee_scolaire_active  (reglable dans Parametres)
 *   2. l'annee de l'etablissement connecte      (appelle sans argument)
 *   3. AnneeScolaire::courante()               (rentree en septembre)
 *
 * Convention : l'annee scolaire court de septembre (rentree) a juillet.
 *   septembre 2026 -> 2026-2027   |   aout 2026 -> 2025-2026
 *   decembre 2026 -> 2026-2027    |   fevrier 2027 -> 2026-2027
 */
final class AnneeScolaire
{
    /** Mois de la rentree scolaire (1 = janvier). */
    public const MOIS_RENTREE = 9;

    public const FORMAT = 'Y-m';

    /**
     * Annee scolaire en cours, calculee depuis une date.
     */
    public static function courante(?CarbonInterface $date = null): string
    {
        $date ??= Carbon::now();

        $debut = $date->month >= self::MOIS_RENTREE ? $date->year : $date->year - 1;

        return $debut . '-' . ($debut + 1);
    }

    /**
     * Annee scolaire active d'un etablissement (ou de l'etablissement connecte).
     */
    public static function active(?Etablissement $etablissement = null): string
    {
        $etablissement ??= self::etablissementConnecte();

        $definie = $etablissement?->annee_scolaire_active;

        if (self::valide($definie)) {
            return $definie;
        }

        return self::courante();
    }

    /**
     * Annee scolaire en base de "AAAA-AAAA" ?
     */
    public static function valide(mixed $annee): bool
    {
        return is_string($annee) && (bool) preg_match('/^\d{4}-\d{4}$/', $annee);
    }

    /**
     * Liste d'annees pour les <select> (active courante par defaut).
     *
     * @param  int  $avant  nombre d'annees proposees avant l'annee de reference
     * @param  int  $apres  nombre d'annees proposees apres l'annee de reference
     * @return list<string>
     */
    public static function liste(?string $reference = null, int $avant = 2, int $apres = 1): array
    {
        $debut = (int) explode('-', self::valide($reference) ? $reference : self::courante())[0];

        $annees = [];
        for ($i = -$avant; $i <= $apres; $i++) {
            $annees[] = ($debut + $i) . '-' . ($debut + $i + 1);
        }

        return $annees;
    }

    /**
     * Annee proposee par defaut dans un formulaire de creation de frais.
     */
    public static function parDefaut(?Etablissement $etablissement = null): string
    {
        return self::active($etablissement);
    }

    private static function etablissementConnecte(): ?Etablissement
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->etablissement_id ? $user->etablissement : null;
    }
}
