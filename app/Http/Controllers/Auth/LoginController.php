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
        $credentials = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? ['email' => $request->login, 'password' => $request->password]
            : ['telephone' => $request->login, 'password' => $request->password];
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

        if ($user->hasRole('parent')) {
            return route('payeur.dashboard');
        }

        // Fallback de sécurité si l'utilisateur n'a aucun rôle reconnu
        return route('landing');
    }
}
