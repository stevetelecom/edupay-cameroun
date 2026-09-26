<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaiementResource;
use App\Models\Paiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\AnneeScolaire;

class PaiementController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Historique des paiements de l'établissement.
     * Par défaut : filtré sur l'année scolaire ACTIVE.
     * Passer annee_scolaire=toutes pour consulter l'historique multi-année,
     * ou annee_scolaire=2025-2026 pour une année précise.
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $etablissement    = auth()->user()->etablissement;
        $anneeActive      = AnneeScolaire::active($etablissement);

        $anneeFiltre  = $request->input('annee_scolaire', $anneeActive);
        $toutesAnnees = $anneeFiltre === 'toutes';

        $paiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->when(! $toutesAnnees, fn ($q) => $q->whereHas(
                'fraisApprenant',
                fn ($f) => $f->where('annee_scolaire', $anneeFiltre)
            ))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('apprenant_id'), fn ($q) => $q->where('apprenant_id', $request->apprenant_id))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference', 'like', "%{$term}%")
                        ->orWhereHas('apprenant', fn ($a) => $a->where('nom', 'like', "%{$term}%")->orWhere('prenom', 'like', "%{$term}%"));
                });
            })
            ->latest('date_paiement')
            ->paginate($request->integer('per_page', 20));

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

        return response()->json([
            'data' => PaiementResource::collection($paiements),
            'meta' => [
                'current_page'       => $paiements->currentPage(),
                'last_page'          => $paiements->lastPage(),
                'total'              => $paiements->total(),
                'per_page'           => $paiements->perPage(),
                'annee_active'       => $anneeActive,
                'annee_filtre'       => $anneeFiltre,
                'annees_disponibles' => $anneesDisponibles,
            ],
        ]);
    }

    private function autoriser(): int
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, __('api.acces_etablissement'));
        }

        return $user->etablissement_id;
    }
}
