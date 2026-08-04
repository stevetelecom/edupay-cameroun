<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            $user = $request->user();

            // Vérifier que le compte n'est pas suspendu avant toute autre chose
            if ($user->suspendu) {
                Auth::logout();
                return back()
                    ->with('error', 'Votre compte a été suspendu. Contactez le support EduPay pour plus d\'informations.')
                    ->withInput();
            }

            // Vérification systématique pour tout utilisateur lié à un établissement,
            // quel que soit le chemin de connexion emprunté (avec ou sans login_type).
            if ($user->hasAnyRole(['directeur', 'comptable', 'caissier'])) {
                $etablissement = $user->etablissement;
                if ($etablissement && $etablissement->statut !== 'actif') {
                    Auth::logout();
                    $message = match($etablissement->statut) {
                        'en_attente' => 'Votre dossier est en cours d\'examen par l\'équipe EduPay. Vous serez notifié(e) par email dès activation à l\'adresse ' . $user->email . '.',
                        'suspendu'   => 'Votre établissement a été suspendu. Contactez le support EduPay pour plus d\'informations.',
                        default      => 'Votre compte établissement n\'est pas encore actif.',
                    };
                    return back()
                        ->with('error', $message)
                        ->withInput();
                }
            }

            if ($request->input('login_type') === 'etablissement') {
                if (! $user->hasAnyRole(['directeur', 'comptable', 'caissier'])) {
                    Auth::logout();
                    return back()
                        ->with('error', 'Ce compte n\'a pas accès au back-office établissement.')
                        ->withInput();
                }

                $request->session()->regenerate();
                return redirect()->intended(route('etablissement.dashboard'))
                    ->with('success', 'Connexion établissement réussie !');
            }

            $request->session()->regenerate();
            $redirect = $this->redirectionParRole($user);
            return redirect()->intended($redirect)
                ->with('success', 'Connexion réussie !');
        }

        return back()
            ->with('error', 'Identifiants incorrects.')
            ->withInput();
    }
    public function showOtpForm(): View
    {
        return view('auth.otp');
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
        ]);

        $login = $request->login;

        // Chercher l'utilisateur (email ou téléphone)
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = \App\Models\User::where('email', $login)->first();
        } else {
            $telephone = preg_replace('/[\s\-\+]/', '', $login);
            if (strlen($telephone) > 9) {
                $telephone = substr($telephone, -9);
            }
            $user = \App\Models\User::where('telephone', $telephone)->first();
        }

        if (!$user) {
            return back()
                ->with('error', 'Utilisateur non trouvé.')
                ->withInput();
        }

        // Si l'OTP n'a pas encore été envoyé, générer et envoyer
        if (!$request->filled('otp_code')) {
            $otp = random_int(100000, 999999);
            $request->session()->put('otp_code', $otp);
            $request->session()->put('otp_login', $login);
            $request->session()->put('otp_user_id', $user->id);
            $request->session()->put('otp_attempts', 0);

            // À implémenter Sprint 2 : envoyer par SMS via AangaraaPay ou autre service SMS
            // Pour le test : afficher le code dans les logs
            Log::info("OTP Code for {$login}: {$otp}");

            return back()
                ->with('otp_sent', true)
                ->with('info', "Code OTP envoyé à {$login}");
        }

        // Vérifier le code OTP
        $request->validate([
            'otp_code' => 'required|numeric|digits:6',
        ]);

        $storedOtp = $request->session()->get('otp_code');
        $attempts = $request->session()->get('otp_attempts', 0);

        if ($attempts >= 3) {
            $request->session()->forget(['otp_code', 'otp_login', 'otp_user_id', 'otp_attempts']);
            return back()
                ->with('error', 'Trop de tentatives. Veuillez recommencer.')
                ->withInput();
        }

        if ((int) $request->otp_code !== (int) $storedOtp) {
            $request->session()->put('otp_attempts', $attempts + 1);
            return back()
                ->with('error', 'Code OTP invalide.')
                ->withInput();
        }

        // Vérifier que le compte n'est pas suspendu avant d'authentifier
        if ($user->suspendu) {
            $request->session()->forget(['otp_code', 'otp_login', 'otp_user_id', 'otp_attempts']);
            return back()
                ->with('error', 'Votre compte a été suspendu. Contactez le support EduPay pour plus d\'informations.')
                ->withInput();
        }

        // Code correct : authentifier l'utilisateur
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget(['otp_code', 'otp_login', 'otp_user_id', 'otp_attempts']);

        return redirect()->intended($this->redirectionParRole($user));
    }
    public function logout(Request $request)
    {
        $redirectVersLogin = $request->input('redirect') === 'login';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($redirectVersLogin ? 'login' : 'landing');
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
