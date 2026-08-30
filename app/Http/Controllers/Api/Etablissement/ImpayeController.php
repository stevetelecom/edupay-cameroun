<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Resources\FraisResource;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImpayeController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Liste des frais impayés de l'établissement + synthèse (équivalent web ImpayeController::index).
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $anneeScolaire   = '2025-2026';

        $fraisImpayes = FraisApprenant::with(['apprenant', 'categorieFrais'])
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', function ($q) use ($etablissementId, $request) {
                $q->where('etablissement_id', $etablissementId);
                if ($request->filled('classe')) {
                    $q->where('classe', $request->classe);
                }
            })
            ->orderByDesc('montant_total')
            ->paginate($request->integer('per_page', 20));

        $totalImpaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get()
            ->sum(fn ($f) => $f->montant_total - $f->montant_paye);

        $totalAttendu = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_total');

        $totalPaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_paye');

        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100) : 0;

        return response()->json([
            'data' => [
                'synthese' => [
                    'total_impaye'      => (int) $totalImpaye,
                    'total_attendu'     => (int) $totalAttendu,
                    'total_paye'        => (int) $totalPaye,
                    'taux_recouvrement' => (int) $tauxRecouvrement,
                ],
                'frais_impayes' => FraisResource::collection($fraisImpayes),
                'pagination'    => [
                    'current_page' => $fraisImpayes->currentPage(),
                    'last_page'    => $fraisImpayes->lastPage(),
                    'total'        => $fraisImpayes->total(),
                    'per_page'     => $fraisImpayes->perPage(),
                ],
                'classes' => Apprenant::where('etablissement_id', $etablissementId)
                    ->distinct()->orderBy('classe')->pluck('classe'),
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
