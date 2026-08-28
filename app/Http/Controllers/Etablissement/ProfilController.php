<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Traits\TelephoneCamerounais;

class ProfilController extends Controller
{
    use TelephoneCamerounais;

    public function index()
    {
        return view('etablissement.profil', [
            'user' => Auth::user(),
        ]);
    }

    public function updateInfos(Request $request)
    {
        $user = Auth::user();

        if ($request->filled('telephone')) {
            $request->merge(['telephone' => $this->normaliserTelephoneCm((string) $request->input('telephone'))]);
        }

        $validated = $request->validate([
            'prenom'    => 'required|string|max:100',
            'nom'       => 'required|string|max:100',
            'telephone' => ['required', 'regex:/^6\d{8}$/', 'unique:users,telephone,' . $user->id],
            'email'     => 'nullable|email|max:150|unique:users,email,' . $user->id,
            'ville'     => 'nullable|string|max:100',
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

        return back()->with('success', 'Informations mises à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        if (!Hash::check($request->current_password ?? '', $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.'])->withInput();
        }

        $request->validate([
            'current_password' => 'required|string',
            'password'         => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
                'different:current_password',
            ],
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'password.min'              => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'        => 'Les mots de passe ne correspondent pas.',
            'password.regex'            => 'Le mot de passe doit contenir 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.',
            'password.different'        => 'Le nouveau mot de passe doit être différent de l\'ancien.',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success_password', 'Mot de passe modifié avec succès. Authentification renforcée.');
    }
}
