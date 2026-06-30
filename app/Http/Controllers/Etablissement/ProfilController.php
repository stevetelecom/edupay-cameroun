<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    public function index()
    {
        return view('etablissement.profil', [
            'user' => Auth::user(),
        ]);
    }

    public function updateInfos(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'prenom'    => 'required|string|max:100',
            'nom'       => 'required|string|max:100',
            'telephone' => 'required|string|max:20|unique:users,telephone,' . $user->id,
            'email'     => 'nullable|email|max:150|unique:users,email,' . $user->id,
            'ville'     => 'nullable|string|max:100',
        ]);

        $user->update([
            'prenom'    => $validated['prenom'],
            'nom'       => $validated['nom'],
            'telephone' => $validated['telephone'],
            'email'     => $validated['email'] ?? null,
            'ville'     => $validated['ville'] ?? null,
        ]);

        return back()->with('success', 'Informations mises à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'password.min'              => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'        => 'Les mots de passe ne correspondent pas.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success_password', 'Mot de passe modifié avec succès.');
    }
}
