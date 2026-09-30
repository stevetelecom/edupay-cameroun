<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
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

        // Le callback partira sur cet hote-la, pas sur celui qui a emis la
        // demande. Si les deux different, Google renvoie le navigateur vers un
        // AUTRE serveur, dont la session ne contient pas le `state` : la
        // connexion echoue toujours avec « Connexion Google annulée » et AUCUNE
        // trace n'apparait dans le log du serveur qui a lance la demande.
        // C'est exactement ce qui rendait ce bug invisible en local.
        $hoteCallback = $this->origineDuCallback();
        $hoteActuel   = request()->getHttpHost();

        if ($hoteCallback !== null && $hoteCallback !== $hoteActuel) {
            Log::warning('Google OAuth : l\'URI de retour pointe sur un autre hote que la demande, la connexion echouera', [
                'hote_demande'  => $hoteActuel,
                'hote_callback' => $hoteCallback,
                'a_corriger'    => 'GOOGLE_REDIRECT_URI doit pointer sur ' . request()->getSchemeAndHttpHost() . '/auth/google/callback',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Normalise l'origine de l'URI de retour sous la forme `hote[:port]`, avec
     * le meme format que `Request::getHttpHost()`.
     *
     * `parse_url(..., PHP_URL_HOST)` retire le port : comparer directement son
     * resultat a `getHttpHost()` signalait a tort une divergence des que le
     * port n'etait pas le port par defaut du schema (cas de `localhost:8000`).
     * Les ports implicites sont donc omis, comme le fait le navigateur.
     */
    private function origineDuCallback(): ?string
    {
        $url = (string) config('services.google.redirect');

        if ($url === '') {
            return null;
        }

        $hote = parse_url($url, PHP_URL_HOST);

        if ($hote === null || $hote === '') {
            return null;
        }

        $port        = parse_url($url, PHP_URL_PORT);
        $portImplicite = parse_url($url, PHP_URL_SCHEME) === 'https' ? 443 : 80;

        if ($port !== null && (int) $port !== $portImplicite) {
            return $hote . ':' . $port;
        }

        return $hote;
    }

    /**
     * Traite le retour Google et connecte ou crée l'utilisateur (profil parent).
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            // Ce n'est PAS une annulation de l'utilisateur : c'est une
            // erreur de configuration. La cause la plus courante est un
            // `GOOGLE_REDIRECT_URI` qui ne pointe pas sur l'hote qui a
            // emis la demande : Google renvoie alors le navigateur sur un
            // autre domaine, dont la session ne contient pas le `state`.
            // L'ancien message « Connexion Google annulée » accusait
            // l'utilisateur d'un probleme qui l'etait pas, et le log
            // n'enregistrait que le message (vide pour cette exception),
            // ce qui rendait le diagnostic impossible.
            Log::warning('Google callback : state invalide (session perdue ou redirect_uri vers un autre hote)', [
                'exception'       => $e::class,
                'redirect_uri'    => config('services.google.redirect'),
                'hote_reçu'       => request()->getHttpHost(),
                'state_reçu'      => request()->input('state') ? 'present' : 'absent',
                'state_en_session'=> (request()->hasSession() && request()->session()->has('state')) ? 'present' : 'absent',
            ]);

            return redirect()->route('login')->with(
                'error',
                'Connexion Google impossible : la session a été perdue entre le clic et le retour de Google. Vérifiez que vous n\'avez pas bloqué les cookies, puis réessayez.'
            );
        } catch (\Throwable $e) {
            Log::error('Google callback : échec de la récupération du profil', [
                'exception' => $e::class,
                'message'   => $e->getMessage(),
                'redirect_uri' => config('services.google.redirect'),
                'hote_reçu' => request()->getHttpHost(),
            ]);

            return redirect()->route('login')->with('error', 'Connexion Google annulée ou échouée. Veuillez réessayer.');
        }

        $email = strtolower($socialiteUser->getEmail() ?? '');
        $googleId = (string) $socialiteUser->getId();

        if (empty($email)) {
            return redirect()->route('login')->with('error', 'Google n\'a pas fourni d\'adresse email. Utilisez la connexion classique.');
        }

        // Google renvoie `email_verified` a false dans certains cas (compte
        // cree recemment, adresse en cours de validation). Sans ce controle,
        // quelqu'un pourrait se connecter avec une adresse qui n'est pas la
        // sienne et prendre le controle du compte EduPay deja rattache a cet
        // email : le code ci-dessous rattache un compte existant par son
        // email. On refuse donc tout email non verifie par Google.
        $emailVerifie = $socialiteUser->user['email_verified'] ?? null;

        if ($emailVerifie !== null && ! $emailVerifie) {
            Log::warning('Google callback : email non verifie par Google, connexion refusee', [
                'google_id' => $googleId,
                'email'     => $email,
            ]);

            return redirect()->route('login')->with('error', 'Votre adresse Google n\'est pas vérifiée. Vérifiez-la chez Google puis réessayez.');
        }

        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if (! $user) {
            // Aucun compte : création automatique d'un profil parent rattaché à son email Google
            $parts = explode(' ', trim($socialiteUser->getName() ?? ''));
            $prenom = array_shift($parts) ?: $email;
            $nom    = $parts ? implode(' ', $parts) : $prenom;

            try {
                $user = User::create([
                    'prenom'      => $prenom,
                    'nom'         => $nom,
                    'email'       => $email,
                    'google_id'   => $googleId,
                    'profil'      => 'parent',
                    'suspendu'    => false,
                    'notif_sms'   => true,
                    'notif_email' => true,
                    'notif_rappel_echeance' => true,
                    // BUG 500 CONNEXION GOOGLE — `users.password` est
                    // NOT NULL sans valeur par défaut, et ce chemin est le
                    // SEUL à ne pas fournir de mot de passe (l'inscription
                    // classique fait toujours Hash::make). L'INSERT levait
                    // donc « Field 'password' doesn't have a default value »
                    // et la connexion renvoyait 500. Le symptôme était
                    // piégeux : seule la PREMIÈRE connexion Google d'un
                    // nouveau compte plantait, car un email déjà présent en
                    // base passait par la branche `else` et fonctionnait.
                    //
                    // Mot de passe aléatoire de 64 caractères : la colonne est
                    // alimentée, mais le compte reste inexploitable en
                    // password login (aucun mot de passe devinable).
                    'password'    => Hash::make(Str::random(64)),
                ]);
            } catch (\Throwable $e) {
                Log::error('Google callback : création du compte impossible', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('login')->with('error', 'Impossible de créer votre compte pour le moment. Contactez le support.');
            }

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
