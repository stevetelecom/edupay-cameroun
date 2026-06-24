<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Mail\InvitationUtilisateurMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UtilisateurController extends Controller
{
    private const ROLES_INTERNES = ['directeur', 'comptable', 'caissier'];

    private const ROLE_LABELS = [
        'directeur' => 'Directeur',
        'comptable' => 'Comptable',
        'caissier'  => 'Caissier',
    ];

    private const ROLE_PERMISSIONS = [
        'directeur' => 'Admin complet',
        'comptable' => 'Saisie + lecture',
        'caissier'  => 'Saisie seule',
    ];

    public function index(): View
    {
        $etablissementId = Auth::user()->etablissement_id;

        $utilisateurs = User::where('etablissement_id', $etablissementId)
            ->with('roles')
            ->get()
            ->filter(fn ($u) => $u->roles->pluck('name')->intersect(self::ROLES_INTERNES)->isNotEmpty());

        return view('etablissement.utilisateurs.index', [
            'utilisateurs'     => $utilisateurs,
            'roleLabels'       => self::ROLE_LABELS,
            'rolePermissions'  => self::ROLE_PERMISSIONS,
            'estDirecteur'     => Auth::user()->hasRole('directeur'),
        ]);
    }

    /**
     * Invite un nouvel utilisateur interne (directeur, comptable ou caissier).
     * Génère un mot de passe temporaire et envoie un email de bienvenue.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->autoriserGestion();

        $validated = $request->validate([
            'prenom'    => 'required|string|max:100',
            'nom'       => 'required|string|max:100',
            'email'     => 'required|email|max:150|unique:users,email',
            'telephone' => 'required|string|max:20|unique:users,telephone',
            'role'      => ['required', Rule::in(self::ROLES_INTERNES)],
        ], [
            'email.unique'     => 'Cette adresse email est déjà utilisée.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
        ]);

        $motDePasseTemporaire = Str::password(10);

        $utilisateur = User::create([
            'prenom'           => $validated['prenom'],
            'nom'              => $validated['nom'],
            'email'            => $validated['email'],
            'telephone'        => $validated['telephone'],
            'password'         => Hash::make($motDePasseTemporaire),
            'etablissement_id' => Auth::user()->etablissement_id,
        ]);

        $utilisateur->assignRole($validated['role']);

        try {
            Mail::to($utilisateur->email)->send(
                new InvitationUtilisateurMail(
                    $utilisateur,
                    $motDePasseTemporaire,
                    self::ROLE_LABELS[$validated['role']]
                )
            );
            $messageSucces = $utilisateur->prenom . ' a été invité(e) avec succès. Un email avec ses identifiants lui a été envoyé.';
        } catch (\Throwable $e) {
            $messageSucces = $utilisateur->prenom . ' a été créé(e), mais l\'envoi de l\'email a échoué. '
                . 'Mot de passe temporaire : ' . $motDePasseTemporaire;
        }

        return redirect()->route('etablissement.utilisateurs.index')
            ->with('success', $messageSucces);
    }

    /**
     * Modifie le rôle d'un utilisateur interne existant.
     */
    public function updateRole(Request $request, User $utilisateur): RedirectResponse
    {
        $this->autoriserGestion();
        $this->autoriserAcces($utilisateur);

        $validated = $request->validate([
            'role' => ['required', Rule::in(self::ROLES_INTERNES)],
        ]);

        if ($utilisateur->id === Auth::id() && $validated['role'] !== 'directeur') {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre rôle de directeur.');
        }

        $utilisateur->syncRoles([$validated['role']]);

        return redirect()->route('etablissement.utilisateurs.index')
            ->with('success', 'Rôle de ' . $utilisateur->prenom . ' mis à jour avec succès.');
    }

    /**
     * Désactive (supprime) un utilisateur interne.
     */
    public function destroy(User $utilisateur): RedirectResponse
    {
        $this->autoriserGestion();
        $this->autoriserAcces($utilisateur);

        if ($utilisateur->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $nom = $utilisateur->prenom . ' ' . $utilisateur->nom;
        $utilisateur->delete();

        return redirect()->route('etablissement.utilisateurs.index')
            ->with('success', $nom . ' a été retiré(e) de l\'équipe.');
    }

    /**
     * Seul le directeur peut inviter / modifier / supprimer.
     */
    private function autoriserGestion(): void
    {
        abort_unless(Auth::user()->hasRole('directeur'), 403, 'Seul le directeur peut gérer les utilisateurs internes.');
    }

    /**
     * Empêche d'agir sur un utilisateur d'un autre établissement.
     */
    private function autoriserAcces(User $utilisateur): void
    {
        if ($utilisateur->etablissement_id !== Auth::user()->etablissement_id) {
            abort(403, 'Accès non autorisé à cet utilisateur.');
        }
    }
}
