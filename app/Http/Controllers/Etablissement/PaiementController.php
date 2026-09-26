<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\AnneeScolaire;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;
        $etablissement    = Auth::user()->etablissement;
        $anneeActive      = AnneeScolaire::active($etablissement);

        // "Toutes les années" est un choix explicite (historique) — sinon on
        // reste toujours sur l'année active par défaut, jamais un mélange muet.
        $anneeFiltre = $request->input('annee_scolaire', $anneeActive);
        $toutesAnnees = $anneeFiltre === 'toutes';

        $paiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->when(! $toutesAnnees, fn ($q) => $q->whereHas(
                'fraisApprenant',
                fn ($f) => $f->where('annee_scolaire', $anneeFiltre)
            ))
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
            ->when(! $toutesAnnees, fn ($q) => $q->whereHas(
                'fraisApprenant',
                fn ($f) => $f->where('annee_scolaire', $anneeFiltre)
            ))
            ->sum('montant');

        $totalEnAttente = Paiement::where('statut', 'en_attente')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->when(! $toutesAnnees, fn ($q) => $q->whereHas(
                'fraisApprenant',
                fn ($f) => $f->where('annee_scolaire', $anneeFiltre)
            ))
            ->sum('montant');

        // Liste des années disponibles pour le sélecteur (toujours au moins l'année active)
        $anneesDisponibles = \App\Models\FraisApprenant::whereHas(
                'apprenant',
                fn ($q) => $q->where('etablissement_id', $etablissementId)
            )
            ->distinct()
            ->orderByDesc('annee_scolaire')
            ->pluck('annee_scolaire');

        if (! $anneesDisponibles->contains($anneeActive)) {
            $anneesDisponibles = $anneesDisponibles->push($anneeActive)->sortDesc()->values();
        }

        return view('etablissement.paiements.index', compact(
            'paiements', 'totalValide', 'totalEnAttente',
            'anneeActive', 'anneeFiltre', 'anneesDisponibles'
        ));
    }
}
