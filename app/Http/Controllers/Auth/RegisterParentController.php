<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterParentController extends Controller
{
    /**
     * Affiche le formulaire unique d'inscription parent.
     * Le bandeau "1 Compte / 2 Enfant(s) / 3 Confirmation" dans la
     * maquette est purement visuel — tout est sur une seule page,
     * un seul submit (conforme à EduPay_Maquette_Interactive.html).
     */
    public function step1(): View
    {
        return view('auth.register-parent', [
            'old' => session('register_parent_old', []),
        ]);
    }

    /**
     * Création du compte parent — POST sur register.parent.step1.post
     * (route existante, on ne touche pas au blade ni à web.php).
     * C'est ICI que tout se passe désormais : validation + création
     * + connexion auto + redirection vers le dashboard.
     */
    public function storeStep1(Request $request)
    {
        $validated = $request->validate([
            'prenom'    => 'required|string|max:100',
            'nom'       => 'required|string|max:100',
            'telephone' => 'required|string|max:20|unique:users,telephone',
            'email'     => 'nullable|email|max:150|unique:users,email',
            'ville'     => 'required|string|max:100',
            'quartier'  => 'nullable|string|max:100',
            'password'  => 'required|string|min:8|confirmed',

            'nom_etablissement'  => 'nullable|string|max:150',
            'code_etablissement' => 'nullable|string|max:50|exists:etablissements,code_etablissement',

            'cgu_accepted'           => 'required|accepted',
            'notifications_accepted' => 'nullable|boolean',
        ], [
            'telephone.unique'          => 'Ce numéro de téléphone est déjà utilisé.',
            'email.unique'              => 'Cette adresse email est déjà utilisée.',
            'password.confirmed'        => 'Les mots de passe ne correspondent pas.',
            'cgu_accepted.accepted'     => 'Vous devez accepter les conditions d\'utilisation.',
            'code_etablissement.exists' => 'Ce code établissement n\'existe pas.',
        ]);

        $etablissementId = null;
        if (!empty($validated['code_etablissement'])) {
            $etablissementId = Etablissement::where(
                'code_etablissement', $validated['code_etablissement']
            )->value('id');
        }

        $user = User::create([
            'name'             => $validated['prenom'] . ' ' . $validated['nom'],
            'telephone'        => $validated['telephone'],
            'email'            => $validated['email'] ?? null,
            'ville'            => $validated['ville'],
            'quartier'         => $validated['quartier'] ?? null,
            'password'         => Hash::make($validated['password']),
            'etablissement_id' => $etablissementId,
        ]);

        $user->assignRole('parent');

        Auth::login($user);

        return redirect()->route('payeur.dashboard')
            ->with('success', 'Bienvenue sur EduPay, ' . $validated['prenom'] . ' !');
    }

    // Conservées pour compatibilité avec les routes existantes
    // (register.parent.step2 / register.parent.confirm),
    // mais ne sont plus utilisées dans le flow réel — tout
    // passe désormais par storeStep1() en un seul submit.
    public function step2(): View
    {
        return redirect()->route('register.parent.step1');
    }

    public function storeStep2()
    {
        return redirect()->route('register.parent.step1');
    }

    public function confirm(): View
    {
        return redirect()->route('register.parent.step1');
    }

    public function store(Request $request)
    {
        return $this->storeStep1($request);
    }
}
