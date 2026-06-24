<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterParentController extends Controller
{
    // STEP 1 — Affiche le formulaire (maquette s-register-parent)
    public function step1(): View
    {
        return view('auth.register-parent');
    }

    // POST step1 — Création du compte
    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profil'       => 'required|in:parent,eleve,etudiant',
            'prenom'       => 'required|string|max:100',
            'nom'          => 'required|string|max:100',
            'telephone'    => 'required|string|max:20|unique:users,telephone',
            'email'        => 'nullable|email|max:150|unique:users,email',
            'ville'        => 'required|string|max:100',
            'quartier'     => 'nullable|string|max:100',
            'password'     => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
            ],
            'notif_sms'    => 'nullable|boolean',
            'notif_email'  => 'nullable|boolean',
            'cgu_accepted' => 'required|accepted',
        ], [
            'profil.required'       => 'Veuillez choisir votre profil.',
            'profil.in'             => 'Profil invalide.',
            'prenom.required'       => 'Le prénom est obligatoire.',
            'nom.required'          => 'Le nom est obligatoire.',
            'telephone.required'    => 'Le numéro de téléphone est obligatoire.',
            'telephone.unique'      => 'Ce numéro est déjà utilisé.',
            'email.unique'          => 'Cette adresse email est déjà utilisée.',
            'password.min'          => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'    => 'Les mots de passe ne correspondent pas.',
            'password.regex'        => 'Le mot de passe doit contenir 1 majuscule, 1 chiffre et 1 caractère spécial.',
            'cgu_accepted.accepted' => 'Vous devez accepter les conditions d\'utilisation.',
        ]);

        $user = User::create([
            'prenom'      => $validated['prenom'],
            'nom'         => $validated['nom'],
            'name'        => $validated['prenom'] . ' ' . $validated['nom'],
            'telephone'   => $validated['telephone'],
            'email'       => $validated['email'] ?? null,
            'ville'       => $validated['ville'],
            'quartier'    => $validated['quartier'] ?? null,
            'profil'      => $validated['profil'],
            'notif_sms'   => $request->boolean('notif_sms'),
            'notif_email' => $request->boolean('notif_email'),
            'password'    => Hash::make($validated['password']),
        ]);

        $role = match($validated['profil']) {
            'eleve', 'etudiant' => 'eleve',
            default             => 'parent',
        };
        $user->assignRole($role);

        Auth::login($user);

        return redirect()->route('payeur.onboarding')
            ->with('success', 'Bienvenue sur EduPay, ' . $validated['prenom'] . ' !');
    }

    // Stubs — compatibilité routes existantes
    public function step2(): RedirectResponse
    {
        return redirect()->route('register.parent.step1');
    }

    public function storeStep2(): RedirectResponse
    {
        return redirect()->route('register.parent.step1');
    }

    public function confirm(): RedirectResponse
    {
        return redirect()->route('register.parent.step1');
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->storeStep1($request);
    }
}
