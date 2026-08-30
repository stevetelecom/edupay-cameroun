<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaiementResource;
use App\Models\Paiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Historique des paiements de l'établissement (filtres par statut / apprenant).
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $paiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
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

        return response()->json([
            'data' => PaiementResource::collection($paiements),
            'meta' => [
                'current_page' => $paiements->currentPage(),
                'last_page'    => $paiements->lastPage(),
                'total'        => $paiements->total(),
                'per_page'     => $paiements->perPage(),
            ],
        ]);
    }

    private function autoriser(): int
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, 'Ce compte n\'a pas accès au back-office établissement.');
        }

        return $user->etablissement_id;
    }
}
