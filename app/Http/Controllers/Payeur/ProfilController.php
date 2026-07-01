<?php

namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function index(): View
    {
        return view('payeur.profil', [
            'pageTitle' => 'Profil & notifications — EduPay',
        ]);
    }

    public function updateInfos(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'prenom'    => 'required|string|max:100',
            'nom'       => 'required|string|max:100',
            'telephone' => 'required|string|max:20|unique:users,telephone,' . $user->id,
            'email'     => 'nullable|email|max:150|unique:users,email,' . $user->id,
        ], [
            'telephone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
            'email.unique'     => 'Cette adresse email est déjà utilisée par un autre compte.',
        ]);

        $user->update([
            'prenom'    => $validated['prenom'],
            'nom'       => $validated['nom'],
            'name'      => $validated['prenom'] . ' ' . $validated['nom'],
            'telephone' => $validated['telephone'],
            'email'     => $validated['email'] ?? null,
        ]);

        return redirect()->route('payeur.profil.index')
            ->with('success', 'Vos informations ont été mises à jour.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $user->update([
            'notif_sms'             => $request->boolean('notif_sms'),
            'notif_email'           => $request->boolean('notif_email'),
            'notif_rappel_echeance' => $request->boolean('notif_rappel_echeance'),
        ]);

        return redirect()->route('payeur.profil.index')
            ->with('success', 'Vos préférences de notification ont été enregistrées.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'          => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
                'different:current_password',
            ],
        ], [
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.regex'     => 'Le mot de passe doit contenir 1 majuscule, 1 chiffre et 1 caractère spécial.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('payeur.profil.index')
            ->with('success', 'Votre mot de passe a été modifié avec succès.');
    }
}
