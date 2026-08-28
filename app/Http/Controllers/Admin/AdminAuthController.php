<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Admin2FAMail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Traits\TelephoneCamerounais;

class AdminAuthController extends Controller
{
    use TelephoneCamerounais;

    /**
     * Affiche le formulaire de connexion Super Admin.
     * URL cachée — non indexée, non listée dans la navigation publique.
     */
    public function showLoginForm()
    {
        return view('admin.login', [
            'pageTitle' => 'Super Admin — EduPay Cameroun',
        ]);
    }

    /**
     * Étape 1 : vérification email + mot de passe.
     * Si valide, génère un code 2FA et redirige vers la page OTP.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'L\'adresse email est obligatoire.',
            'email.email'       => 'Format d\'email invalide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        // Limitation des tentatives : 5 max par tranche de 15 minutes
        $loginKey = 'admin_login_' . $request->ip() . '_' . $request->email;
        if (Cache::has($loginKey . '_blocked')) {
            AuditLog::enregistrerSansUser(
                'LOGIN_BLOQUE',
                'IP bloquée après 5 tentatives : ' . $request->ip(),
                $request,
                'CRITICAL'
            );
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Réessayez dans 15 minutes.',
            ]);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (! $admin) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        if (! Hash::check($request->password, $admin->password)) {
            $attempts = Cache::increment($loginKey . '_attempts');
            if ($attempts >= 5) {
                Cache::put($loginKey . '_blocked', true, now()->addMinutes(15));
                Cache::forget($loginKey . '_attempts');
            }

            AuditLog::enregistrerSansUser(
                'LOGIN_ECHEC',
                'Email : ' . $request->email . ' — IP : ' . $request->ip(),
                $request,
                'WARNING'
            );

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        Cache::forget($loginKey . '_attempts');

        // Génération du code OTP 2FA (6 chiffres, valable 5 minutes)
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put(
            '2fa_admin_' . $admin->id,
            Hash::make($otpCode),
            now()->addMinutes(5)
        );

        // Stocker l'admin_id en session pour l'étape 2FA
        $request->session()->put('admin_2fa_id', $admin->id);

        // Envoi du code 2FA par email
        try {
            Mail::to($admin->email)->send(new Admin2FAMail($admin, $otpCode));
            Log::channel('admin')->info('Code 2FA envoyé par email à ' . $admin->email);
        } catch (\Throwable $e) {
            Log::channel('admin')->error('Échec envoi email 2FA : ' . $e->getMessage());
            Log::channel('admin')->info('Code 2FA (fallback log) pour ' . $admin->email . ' : ' . $otpCode);
        }
        AuditLog::enregistrer(
            $admin,
            'LOGIN_2FA_ENVOYE',
            'Code 2FA envoyé à ' . substr($admin->telephone, 0, 3) . '****',
            $request,
            'INFO'
        );

        return redirect()->route('admin.login.2fa')
            ->with('info', 'Un code à 6 chiffres a été envoyé sur votre téléphone.');
    }

    /**
     * Affiche le formulaire de saisie du code 2FA.
     */
    public function show2fa(Request $request)
    {
        if (! $request->session()->has('admin_2fa_id')) {
            return redirect()->route('admin.login')
                ->with('error', 'Session expirée. Veuillez recommencer.');
        }

        return view('admin.login-2fa', [
            'pageTitle' => 'Vérification 2FA — Super Admin EduPay',
        ]);
    }

    /**
     * Étape 2 : vérification du code OTP 2FA.
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'Le code de vérification est obligatoire.',
            'code.digits'   => 'Le code doit contenir exactement 6 chiffres.',
        ]);

        $adminId = $request->session()->get('admin_2fa_id');
        if (! $adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Session expirée. Veuillez recommencer.');
        }

        /** @var Admin $admin */
        $admin = Admin::findOrFail($adminId);

        // Limite de tentatives anti brute-force (5 max / 15 min)
        $attemptsKey = 'admin_2fa_attempts_' . $admin->id;
        if (Cache::get($attemptsKey, 0) >= 5) {
            $request->session()->forget('admin_2fa_id');
            Cache::forget('2fa_admin_' . $admin->id);
            Cache::forget($attemptsKey);

            AuditLog::enregistrer(
                $admin, 'LOGIN_2FA_BLOQUE',
                'Trop de tentatives 2FA — session invalidée.',
                $request, 'CRITICAL'
            );

            return redirect()->route('admin.login')
                ->with('error', 'Trop de tentatives. Reconnectez-vous.');
        }

        $hashedOtp = Cache::get('2fa_admin_' . $admin->id);

        if (! $hashedOtp || ! Hash::check($request->code, $hashedOtp)) {
            Cache::put($attemptsKey, Cache::get($attemptsKey, 0) + 1, now()->addMinutes(15));

            AuditLog::enregistrer(
                $admin,
                'LOGIN_2FA_ECHEC',
                'Code 2FA incorrect.',
                $request,
                'WARNING'
            );

            return back()->withErrors(['code' => 'Code incorrect ou expiré.']);
        }

        Cache::forget($attemptsKey);

        // Supprimer le code OTP après utilisation
        Cache::forget('2fa_admin_' . $admin->id);
        $request->session()->forget('admin_2fa_id');

        // Connecter l'admin via le garde 'admin'
        Auth::guard('admin')->login($admin, remember: true);

        // Mettre à jour la dernière connexion
        $admin->update([
            'derniere_connexion'    => now(),
            'derniere_connexion_ip' => $request->ip(),
        ]);

        AuditLog::enregistrer(
            $admin,
            'LOGIN_SUCCES',
            'Connexion Super Admin réussie depuis ' . $request->ip(),
            $request,
            'INFO'
        );

        return redirect()->route('admin.dashboard')
            ->with('success', 'Bienvenue, ' . $admin->prenom . '. Connexion sécurisée.');
    }


    // ─────────────────────────────────────────────
    // Reset mot de passe admin
    // ─────────────────────────────────────────────

    public function showForgotForm()
    {
        return view('admin.forgot-password', [
            'pageTitle' => 'Réinitialisation — Super Admin EduPay',
        ]);
    }

    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => "L'email est obligatoire.",
            'email.email'    => "Format d'email invalide.",
        ]);

        $admin = Admin::where('email', $request->email)->first();

        // Sécurité : ne pas révéler si l'email existe ou non
        if ($admin) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Cache::put('admin_reset_' . $admin->id, Hash::make($code), now()->addMinutes(10));
            $request->session()->put('admin_reset_id', $admin->id);

            try {
                Mail::to($admin->email)->send(new \App\Mail\AdminResetPasswordMail($admin, $code));
                Log::channel('admin')->info('Code reset envoyé à ' . $admin->email);
            } catch (\Throwable $e) {
                Log::channel('admin')->error('Échec envoi reset : ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.password.reset.form')
            ->with('info', 'Si cet email existe, un code a été envoyé.');
    }

    public function showResetForm(Request $request)
    {
        if (! $request->session()->has('admin_reset_id')) {
            return redirect()->route('admin.password.forgot')
                ->with('error', 'Session expirée. Recommencez.');
        }

        return view('admin.reset-password', [
            'pageTitle' => 'Nouveau mot de passe — Super Admin EduPay',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'code'                  => ['required', 'digits:6'],
            'password'              => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'code.required'         => 'Le code est obligatoire.',
            'code.digits'           => 'Le code doit contenir 6 chiffres.',
            'password.required'     => 'Le mot de passe est obligatoire.',
            'password.min'          => 'Minimum 10 caractères.',
            'password.confirmed'    => 'Les mots de passe ne correspondent pas.',
        ]);

        $adminId = $request->session()->get('admin_reset_id');
        if (! $adminId) {
            return redirect()->route('admin.password.forgot')
                ->with('error', 'Session expirée. Recommencez.');
        }

        // Limite de tentatives sur le code (anti brute-force)
        $codeAttemptsKey = 'admin_reset_attempts_' . $adminId;
        if (Cache::get($codeAttemptsKey, 0) >= 5) {
            $request->session()->forget('admin_reset_id');
            Cache::forget($codeAttemptsKey);
            return redirect()->route('admin.password.forgot')
                ->with('error', 'Trop de tentatives. Recommencez la procédure.');
        }

        $admin      = Admin::findOrFail($adminId);
        $hashedCode = Cache::get('admin_reset_' . $admin->id);

        // 1. Vérifier le code EN PREMIER — avant toute autre logique métier
        if (! $hashedCode || ! Hash::check($request->code, $hashedCode)) {
            Cache::increment($codeAttemptsKey);
            Cache::put($codeAttemptsKey, Cache::get($codeAttemptsKey, 1), now()->addMinutes(15));
            return back()->withErrors(['code' => 'Code incorrect ou expiré.']);
        }

        Cache::forget($codeAttemptsKey);

        // 2. Seulement APRÈS validation du code : vérifier que le nouveau mdp diffère de l'ancien
        if (Hash::check($request->password, $admin->password)) {
            return back()->withErrors(['password' => 'Le nouveau mot de passe doit être différent de l\'ancien.']);
        }

        $admin->update(['password' => bcrypt($request->password)]);
        Cache::forget('admin_reset_' . $admin->id);
        $request->session()->forget('admin_reset_id');

        AuditLog::enregistrerSansUser(
            'PASSWORD_RESET',
            'Mot de passe Super Admin réinitialisé : ' . $admin->email,
            $request,
            'WARNING'
        );

        return redirect()->route('admin.login')
            ->with('success', 'Mot de passe réinitialisé. Connectez-vous.');
    }

    // ─────────────────────────────────────────────
    // Renvoyer le code 2FA
    // ─────────────────────────────────────────────

    public function resend2fa(Request $request)
    {
        $adminId = $request->session()->get('admin_2fa_id');
        if (! $adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Session expirée. Veuillez recommencer.');
        }

        $admin   = Admin::findOrFail($adminId);
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('2fa_admin_' . $admin->id, Hash::make($otpCode), now()->addMinutes(5));

        try {
            Mail::to($admin->email)->send(new Admin2FAMail($admin, $otpCode));
            Log::channel('admin')->info('Code 2FA renvoyé à ' . $admin->email);
        } catch (\Throwable $e) {
            Log::channel('admin')->error('Échec renvoi 2FA : ' . $e->getMessage());
        }

        return redirect()->route('admin.login.2fa')
            ->with('info', 'Un nouveau code a été envoyé à votre adresse email.');
    }

    /**
     * Déconnexion sécurisée Super Admin.
     */
    public function logout(Request $request)
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            AuditLog::enregistrer(
                $admin,
                'LOGOUT',
                'Déconnexion Super Admin.',
                $request,
                'INFO'
            );
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('info', 'Vous avez été déconnecté avec succès.');
    }

    /**
     * Affiche le formulaire d'inscription Super Admin.
     * Protégé par un token secret défini dans .env (ADMIN_REGISTER_TOKEN).
     */
    public function showRegisterForm(\Illuminate\Http\Request $request)
    {
        // Vérifier le token secret dans l'URL : ?token=xxxx
        $token = $request->query('token');
        $expected = config('app.admin_register_token');
        if (! $token || ! $expected || ! hash_equals((string) $expected, (string) $token)) {
            abort(404);
        }

        return view('admin.register', [
            'pageTitle' => 'Créer le Super Admin — EduPay Cameroun',
            'token'     => $token,
        ]);
    }

    /**
     * Créer le compte Super Admin.
     */
    public function register(\Illuminate\Http\Request $request)
    {
        // Revérifier le token
        $token = $request->input('token');
        $expected = config('app.admin_register_token');
        if (! $token || ! $expected || ! hash_equals((string) $expected, (string) $token)) {
            abort(404);
        }

        if ($request->filled('telephone')) {
            $request->merge(['telephone' => $this->normaliserTelephoneCm((string) $request->input('telephone'))]);
        }

        $validated = $request->validate([
            'prenom'     => ['required', 'string', 'max:80'],
            'nom'        => ['required', 'string', 'max:80'],
            'email'      => ['required', 'email', 'unique:admins,email'],
            'telephone'  => ['required', 'regex:/^6\d{8}$/'],
            'password'   => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'prenom.required'    => 'Le prénom est obligatoire.',
            'nom.required'       => 'Le nom est obligatoire.',
            'email.required'     => 'L\'email est obligatoire.',
            'email.unique'       => 'Cet email est déjà utilisé.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.regex'    => 'Numéro invalide. Format attendu : 6XXXXXXXX (9 chiffres, mobile camerounais).',
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.min'       => 'Le mot de passe doit contenir au moins 10 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $admin = Admin::create([
            'prenom'    => $validated['prenom'],
            'nom'       => strtoupper($validated['nom']),
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'],
            'password'  => $validated['password'],
            'est_actif' => true,
        ]);

        AuditLog::enregistrerSansUser(
            'ADMIN_CREE',
            'Super Admin créé : ' . $admin->email . ' depuis ' . $request->ip(),
            $request,
            'CRITICAL'
        );

        Log::channel('admin')->info('Super Admin créé : ' . $admin->email);

        return redirect()->route('admin.login')
            ->with('success', 'Compte Super Admin créé avec succès. Connectez-vous.');
    }

}