<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Suivi des reversements AangaraaPay vers l'établissement.
 *
 * Rend visible pour l'école l'argent qui lui revient réellement (net des
 * frais EduPay) : montant reverse, montant en cours, et surtout les sommes
 * bloquees (a_verifier / echec) qui exigent une action humaine. Sans cette
 * page, un reversement bloque etait invisible pour l'etablissement —
 * seuls les logs et l'email admin le tracaient.
 */
class ReversementController extends Controller
{
    public function index(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;

        $reversements = Commission::with(['paiement.apprenant'])
            ->where('etablissement_id', $etablissementId)
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference_reversement', 'like', "%{$term}%")
                        ->orWhereHas('paiement', function ($p) use ($term) {
                            $p->where('reference', 'like', "%{$term}%")
                              ->orWhereHas('apprenant', function ($a) use ($term) {
                                  $a->where('nom', 'like', "%{$term}%")
                                    ->orWhere('prenom', 'like', "%{$term}%");
                              });
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $base = Commission::where('etablissement_id', $etablissementId);

        $totalReverse   = (clone $base)->where('statut', Commission::STATUT_PRELEVEE)->sum('montant_net_etablissement');
        $totalEnCours   = (clone $base)->whereIn('statut', [Commission::STATUT_CALCULEE, Commission::STATUT_EN_COURS])->sum('montant_net_etablissement');
        $aTraiter       = (clone $base)->whereIn('statut', Commission::STATUTS_A_TRAITER)->sum('montant_net_etablissement');
        $nbATraiter     = (clone $base)->whereIn('statut', Commission::STATUTS_A_TRAITER)->count();

        return view('etablissement.reversements.index', compact(
            'reversements', 'totalReverse', 'totalEnCours', 'aTraiter', 'nbATraiter'
        ));
    }
}