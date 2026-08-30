<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Etablissement\UtilisateurStoreRequest;
use App\Mail\InvitationUtilisateurMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UtilisateurController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    private const ROLE_LABELS = [
        'directeur' => 'Directeur',
        'comptable' => 'Comptable',
        'caissier'  => 'Caissier',
    ];

    /**
     * Liste des utilisateurs internes de l'établissement (directeur/comptable/caissier).
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $utilisateurs = User::where('etablissement_id', $etablissementId)
            ->with('roles')
            ->get()
            ->filter(fn ($u) => $u->roles->pluck('name')->intersect(self::ROLES_ETABLISSEMENT)->isNotEmpty())
            ->values()
            ->map(fn ($u) => [
                'id'          => $u->id,
                'prenom'      => $u->prenom,
                'nom'         => $u->nom,
                'email'       => $u->email,
                'telephone'   => $u->telephone,
                'role'        => $u->roles->pluck('name')->first(),
                'suspendu'    => (bool) $u->suspendu,
            ]);

        return response()->json([
            'data'         => $utilisateurs,
            'role_labels'  => self::ROLE_LABELS,
            'est_directeur' => auth()->user()->hasRole('directeur'),
        ]);
    }

    /**
     * Invite un nouvel utilisateur interne (réservé au directeur).
     */
    public function store(UtilisateurStoreRequest $request): JsonResponse
    {
        $this->autoriserGestion();

        $validated    = $request->validated();
        $motDePasse   = Str::password(10);

        $utilisateur = User::create([
            'prenom'           => $validated['prenom'],
            'nom'              => $validated['nom'],
            'email'            => $validated['email'],
            'telephone'        => $validated['telephone'],
            'password'         => Hash::make($motDePasse),
            'etablissement_id' => auth()->user()->etablissement_id,
        ]);

        $utilisateur->assignRole($validated['role']);

        $emailEnvoye = true;
        try {
            Mail::to($utilisateur->email)->send(
                new InvitationUtilisateurMail(
                    $utilisateur,
                    $motDePasse,
                    self::ROLE_LABELS[$validated['role']]
                )
            );
            $message = $utilisateur->prenom . ' a été invité(e). Un email avec ses identifiants lui a été envoyé.';
        } catch (\Throwable $e) {
            Log::error('API invitation utilisateur : envoi email échoué : ' . $e->getMessage());
            $emailEnvoye = false;
            $message    = $utilisateur->prenom . ' a été créé(e), mais l\'envoi de l\'email a échoué.';
        }

        return response()->json([
            'message'      => $message,
            'email_envoye' => $emailEnvoye,
            // Le mot de passe temporaire n'est renvoyé que si l'email a échoué (sinon envoyé par mail uniquement)
            'mot_de_passe_temporaire' => $emailEnvoye ? null : $motDePasse,
            'data'         => [
                'id'          => $utilisateur->id,
                'prenom'      => $utilisateur->prenom,
                'nom'         => $utilisateur->nom,
                'email'       => $utilisateur->email,
                'role'        => $validated['role'],
            ],
        ], 201);
    }

    /**
     * Modifie le rôle d'un utilisateur interne (réservé au directeur).
     */
    public function updateRole(Request $request, User $utilisateur): JsonResponse
    {
        $this->autoriserGestion();
        $this->autoriserAcces($utilisateur);

        $validated = $request->validate([
            'role' => ['required', Rule::in(self::ROLES_ETABLISSEMENT)],
        ]);

        if ($utilisateur->id === auth()->id() && $validated['role'] !== 'directeur') {
            return response()->json([
                'message' => 'Vous ne pouvez pas retirer votre propre rôle de directeur.',
            ], 422);
        }

        $utilisateur->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Rôle de ' . $utilisateur->prenom . ' mis à jour avec succès.',
            'data'    => [
                'id'   => $utilisateur->id,
                'role' => $validated['role'],
            ],
        ]);
    }

    /**
     * Retire un utilisateur interne (réservé au directeur).
     */
    public function destroy(Request $request, User $utilisateur): JsonResponse
    {
        $this->autoriserGestion();
        $this->autoriserAcces($utilisateur);

        if ($utilisateur->id === auth()->id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        $nom = $utilisateur->prenom . ' ' . $utilisateur->nom;
        $utilisateur->delete();

        return response()->json([
            'message' => $nom . ' a été retiré(e) de l\'équipe.',
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

    private function autoriserGestion(): void
    {
        abort_unless(auth()->user()->hasRole('directeur'), 403, 'Seul le directeur peut gérer les utilisateurs internes.');
    }

    private function autoriserAcces(User $utilisateur): void
    {
        if ($utilisateur->etablissement_id !== auth()->user()->etablissement_id) {
            abort(403, 'Accès non autorisé à cet utilisateur.');
        }
    }
}
