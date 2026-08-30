<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProfilUpdateRequest;
use App\Http\Resources\UserResource;
use App\Traits\TelephoneCamerounais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfilController extends Controller
{
    use TelephoneCamerounais;

    /**
     * Récupère le profil de l'utilisateur connecté.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Met à jour les informations du profil.
     */
    public function update(ProfilUpdateRequest $request): JsonResponse
    {
        $user  = $request->user();
        $valid = $request->validated();

        $user->update([
            'prenom'                => $valid['prenom'] ?? $user->prenom,
            'nom'                   => $valid['nom'] ?? $user->nom,
            'ville'                 => $valid['ville'] ?? $user->ville,
            'quartier'              => $valid['quartier'] ?? $user->quartier,
            'email'                 => $valid['email'] ?? $user->email,
            'notif_sms'             => $valid['notif_sms'] ?? $user->notif_sms,
            'notif_email'           => $valid['notif_email'] ?? $user->notif_email,
            'notif_rappel_echeance' => $valid['notif_rappel_echeance'] ?? $user->notif_rappel_echeance,
        ]);

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user'    => new UserResource($user->fresh()),
        ]);
    }
}
