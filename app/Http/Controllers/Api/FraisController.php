<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FraisResource;
use App\Models\Apprenant;
use Illuminate\Http\JsonResponse;

class FraisController extends Controller
{
    /**
     * Liste des frais d'un apprenant rattaché à l'utilisateur connecté.
     * Bloque l'accès si le rattachement n'est pas validé par l'établissement.
     */
    public function index(Apprenant $apprenant): JsonResponse
    {
        $user = auth()->user();

        $apprenant = $user->apprenants()
            ->where('apprenants.id', $apprenant->id)
            ->with(['etablissement', 'frais.categorieFrais.echeanciers'])
            ->first();

        if (! $apprenant) {
            return response()->json(['message' => 'Cet apprenant ne vous est pas rattaché.'], 403);
        }

        if (! $apprenant->valide_par_etablissement) {
            return response()->json([
                'message' => 'Ce rattachement est en attente de validation par l\'établissement. Vous serez notifié dès qu\'il sera confirmé.',
            ], 403);
        }

        return response()->json([
            'data' => FraisResource::collection($apprenant->frais),
        ]);
    }
}
