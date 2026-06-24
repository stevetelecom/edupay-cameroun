<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;

        $paiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference', 'like', "%{$term}%")
                        ->orWhereHas('apprenant', function ($a) use ($term) {
                            $a->where('nom', 'like', "%{$term}%")
                              ->orWhere('prenom', 'like', "%{$term}%");
                        });
                });
            })
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('mode_paiement'), fn ($q) => $q->where('mode_paiement', $request->mode_paiement))
            ->latest('date_paiement')
            ->paginate(20)
            ->withQueryString();

        $totalValide = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant');

        $totalEnAttente = Paiement::where('statut', 'en_attente')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant');

        return view('etablissement.paiements.index', compact('paiements', 'totalValide', 'totalEnAttente'));
    }
}
