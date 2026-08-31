<?php

namespace App\View\Composers;

use App\Models\FraisApprenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Fournit les indicateurs globaux du back-office établissement à la sidebar
 * (et à toute vue qui en hérite) :
 *   - $tauxRecouvrementDecimal : taux de recouvrement (même calcul que le dashboard)
 *   - $countImpayes            : nombre de dossiers de frais impayés (badge sidebar)
 *
 * ⚠️ Avant ce composer, ces deux variables n'étaient passées QUE par
 *    DashboardController -> la sidebar affichait 0% et aucun badge sur tous
 *    les autres onglets, alors que le tableau de bord affichait la bonne valeur.
 *    Ce composer unifie l'affichage sur TOUTES les pages du back-office.
 */
class EtablissementSidebarComposer
{
    public function compose(View $view): void
    {
        $user = Auth::guard('web')->user();
        $etablissementId = $user?->etablissement_id;

        $tauxRecouvrementDecimal = 0;
        $countImpayes = 0;

        if ($etablissementId) {
            // Même année scolaire que DashboardController / ImpayeController.
            $anneeScolaire = '2025-2026';

            $totalAttendu = FraisApprenant::where('annee_scolaire', $anneeScolaire)
                ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
                ->sum('montant_total');

            $totalPaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
                ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
                ->sum('montant_paye');

            $tauxRecouvrementDecimal = $totalAttendu > 0
                ? round(($totalPaye / $totalAttendu) * 100, 2)
                : 0;

            $countImpayes = FraisApprenant::where('annee_scolaire', $anneeScolaire)
                ->where('statut', '!=', 'regle')
                ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
                ->count();
        }

        $view->with('tauxRecouvrementDecimal', $tauxRecouvrementDecimal)
             ->with('countImpayes', $countImpayes);
    }
}
