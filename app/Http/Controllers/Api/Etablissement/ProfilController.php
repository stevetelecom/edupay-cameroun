<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Traits\TelephoneCamerounais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    use TelephoneCamerounais;

    /**
     * Profil de l'utilisateur back-office (et l'établissement associé).
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $user->load('etablissement', 'roles');

        return response()->json([
            'data' => [
                'id'            => $user->id,
                'prenom'        => $user->prenom,
                'nom'           => $user->nom,
                'email'         => $user->email,
                'telephone'     => $user->telephone,
                'ville'         => $user->ville,
                'role'          => $user->roles->pluck('name')->first(),
                'etablissement' => $user->etablissement ? [
                    'id'              => $user->etablissement->id,
                    'nom'             => $user->etablissement->nom,
                    'type'            => $user->etablissement->type,
                    'ville'           => $user->etablissement->ville,
                    'logo'            => $user->etablissement->logo ? asset('storage/' . $user->etablissement->logo) : null,
                ] : null,
            ],
        ]);
    }

    /**
     * Met à jour les informations du compte back-office.
     */
    public function updateInfos(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($request->filled('telephone')) {
            $request->merge(['telephone' => $this->normaliserTelephoneCm((string) $request->input('telephone'))]);
        }

        $validated = $request->validate([
            'prenom'    => ['required', 'string', 'max:100'],
            'nom'       => ['required', 'string', 'max:100'],
            'telephone' => ['required', 'regex:/^6\d{8}$/', 'unique:users,telephone,' . $user->id],
            'email'     => ['nullable', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'ville'     => ['nullable', 'string', 'max:100'],
        ], [
            'telephone.regex' => 'Numéro invalide. Format attendu : 6XXXXXXXX (9 chiffres, mobile camerounais).',
        ]);

        $user->update([
            'prenom'    => $validated['prenom'],
            'nom'       => $validated['nom'],
            'telephone' => $validated['telephone'],
            'email'     => $validated['email'] ?? null,
            'ville'     => $validated['ville'] ?? null,
        ]);

        return response()->json([
            'message' => 'Informations mises à jour avec succès.',
            'data'    => [
                'id'        => $user->id,
                'prenom'    => $user->prenom,
                'nom'       => $user->nom,
                'telephone' => $user->telephone,
                'email'     => $user->email,
                'ville'     => $user->ville,
            ],
        ]);
    }

    /**
     * Met à jour le mot de passe (renforce l'authentification).
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! Hash::check($request->current_password ?? '', $user->password)) {
            return response()->json([
                'message' => 'Mot de passe actuel incorrect.',
                'errors'  => ['current_password' => ['Mot de passe actuel incorrect.']],
            ], 422);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
                'different:current_password',
            ],
        ], [
            'password.min'       => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.regex'     => 'Le mot de passe doit contenir 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès. Authentification renforcée.',
        ]);
    }
}
