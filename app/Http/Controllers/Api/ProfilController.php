<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProfilUpdateRequest;
use App\Http\Resources\UserResource;
use App\Traits\TelephoneCamerounais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    /**
     * Met à jour les préférences de notification (équivalent web updateNotifications).
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'notif_sms'             => $request->boolean('notif_sms', $user->notif_sms),
            'notif_email'           => $request->boolean('notif_email', $user->notif_email),
            'notif_rappel_echeance' => $request->boolean('notif_rappel_echeance', $user->notif_rappel_echeance),
        ]);

        return response()->json([
            'message' => 'Vos préférences de notification ont été enregistrées.',
            'user'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Met à jour le mot de passe (équivalent web updatePassword).
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
                'different:current_password',
            ],
        ], [
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.regex'     => 'Le mot de passe doit contenir 1 majuscule, 1 chiffre et 1 caractère spécial.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Mot de passe actuel incorrect.',
                'errors'  => ['current_password' => ['Mot de passe actuel incorrect.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json([
            'message' => 'Votre mot de passe a été modifié avec succès.',
        ]);
    }
}
