<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GoogleController extends Controller
{
    /**
     * Redirige vers Google OAuth. Si le client n'est pas configuré dans .env,
     * on renvoie un message clair au lieu d'une erreur 500.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->with('error', 'La connexion Google n\'est pas encore configurée. Utilisez le mot de passe ou contactez le support.');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Traite le retour Google et connecte ou crée l'utilisateur (profil parent).
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google callback : ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Connexion Google annulée ou échouée. Veuillez réessayer.');
        }

        $email = strtolower($socialiteUser->getEmail() ?? '');
        $googleId = (string) $socialiteUser->getId();

        if (empty($email)) {
            return redirect()->route('login')->with('error', 'Google n\'a pas fourni d\'adresse email. Utilisez la connexion classique.');
        }

        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if (! $user) {
            // Aucun compte : création automatique d'un profil parent rattaché à son email Google
            $parts = explode(' ', trim($socialiteUser->getName() ?? ''));
            $prenom = array_shift($parts) ?: $email;
            $nom    = $parts ? implode(' ', $parts) : $prenom;

            $user = User::create([
                'prenom'      => $prenom,
                'nom'         => $nom,
                'name'        => $prenom . ' ' . $nom,
                'email'       => $email,
                'google_id'   => $googleId,
                'profil'      => 'parent',
                'suspendu'    => false,
                'notif_sms'   => true,
                'notif_email' => true,
                'notif_rappel_echeance' => true,
            ]);
            $user->assignRole('parent');
        } else {
            // Lier google_id s'il manque (compte déjà existant par email)
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
        }

        if ($user->suspendu) {
            return redirect()->route('login')->with('error', 'Votre compte a été suspendu. Contactez le support EduPay.');
        }

        Auth::login($user, true);
        session()->regenerate();

        return redirect()->intended($this->redirectionParRole($user))
            ->with('success', 'Connexion Google réussie !');
    }

    private function redirectionParRole($user): string
    {
        if ($user->hasRole('directeur') || $user->hasRole('comptable') || $user->hasRole('caissier')) {
            return route('etablissement.dashboard');
        }

        if ($user->hasRole('parent') || $user->hasRole('eleve')) {
            return route('payeur.dashboard');
        }

        return route('landing');
    }
}
