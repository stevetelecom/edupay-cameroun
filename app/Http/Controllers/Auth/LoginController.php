<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->login;

        // Si c'est un email, chercher directement
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $credentials = ['email' => $login, 'password' => $request->password];
        } else {
            // C'est un numéro de téléphone — normaliser pour la recherche en BD
            // La BD stocke "699123456" mais le formulaire envoie "+237699123456"
            $telephone = preg_replace('/[\s\-\+]/', '', $login); // Retire espaces, tirets, +
            
            // Garder juste les 9 derniers chiffres (Cameroun)
            if (strlen($telephone) > 9) {
                $telephone = substr($telephone, -9);
            }
            
            $credentials = ['telephone' => $telephone, 'password' => $request->password];
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended($this->redirectionParRole($request->user()));
        }

        return back()->withErrors([
            'login' => 'Identifiants incorrects.',
        ])->withInput();
    }
    public function showOtpForm(): View
    {
        return view('auth.otp');
    }
    public function verifyOtp(Request $request)
    {
        // À implémenter Sprint 2 — WANDJI
        return back();
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('landing');
    }

    /**
     * Détermine la route de redirection selon le rôle de l'utilisateur connecté.
     */
    private function redirectionParRole($user): string
    {
        if ($user->hasRole('directeur') || $user->hasRole('comptable') || $user->hasRole('caissier')) {
            return route('etablissement.dashboard');
        }

        if ($user->hasRole('parent') || $user->hasRole('eleve')) {
            return route('payeur.dashboard');
        }

        // Fallback de sécurité si l'utilisateur n'a aucun rôle reconnu
        return route('landing');
    }
}
