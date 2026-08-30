<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReclamationRequest;
use App\Http\Resources\ReclamationResource;
use App\Models\Paiement;
use App\Models\Reclamation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReclamationController extends Controller
{
    /**
     * Liste des réclamations de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $reclamations = Reclamation::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => ReclamationResource::collection($reclamations),
        ]);
    }

    /**
     * Crée une réclamation, éventuellement liée à un paiement.
     */
    public function store(ReclamationRequest $request): JsonResponse
    {
        $valid = $request->validated();

        // Si un paiement est mentionné, vérifier qu'il appartient bien à l'utilisateur.
        if (! empty($valid['paiement_id'])) {
            $paiement = Paiement::where('id', $valid['paiement_id'])
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $paiement) {
                return response()->json([
                    'message' => 'Paiement introuvable ou non autorisé.',
                    'errors'  => ['paiement_id' => ['Paiement invalide.']],
                ], 422);
            }
        }

        $reclamation = Reclamation::create([
            'user_id'     => $request->user()->id,
            'paiement_id' => $valid['paiement_id'] ?? null,
            'sujet'       => $valid['sujet'],
            'description' => $valid['description'],
            'statut'      => 'ouverte',
        ]);

        return response()->json([
            'message' => 'Votre réclamation a été enregistrée.',
            'data'    => new ReclamationResource($reclamation),
        ], 201);
    }
}
