<?php

namespace App\View\Composers;

use App\Services\AangaraaPayService;
use Illuminate\View\View;

/**
 * Fournit le widget « Commission active » de la sidebar Super Admin.
 *
 * Avant ce composer, le layout affichait ($tauxCommission ?? 0.025) : la
 * valeur 2,5 % n'était plus qu'un repli décoratif — le taux réellement
 * appliqué aux paiements vaut taux_aangaraa + marge_edupay (paramètres
 * système modifiables en Super Admin). Le widget affichait donc un chiffre
 * faux sur toutes les pages sauf le dashboard (qui ne passait déjà plus la
 * bonne valeur).
 *
 * Variables injectées dans layouts/admin (sidebar + bandeau dashboard) :
 *   - $tauxAangaraaPct : coût AangaraaPay (2,2 % par défaut)
 *   - $margeEdupayPct  : marge EduPay   (0,1 % par défaut)
 *   - $tauxCommission  : taux global = somme des deux (2,3 % par défaut)
 */
class AdminSidebarComposer
{
    public function compose(View $view): void
    {
        $aangaraa = app(AangaraaPayService::class);

        $tauxAangaraa = $aangaraa->tauxAangaraa(); // 0.022
        $margeEdupay  = $aangaraa->margeEdupay();  // 0.001

        $view->with('tauxAangaraaPct', round($tauxAangaraa * 100, 2))
             ->with('margeEdupayPct', round($margeEdupay * 100, 2))
             ->with('tauxCommission', $tauxAangaraa + $margeEdupay);
    }
}
