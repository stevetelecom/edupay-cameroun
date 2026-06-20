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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
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

        // Envoi du code par SMS Africa's Talking (commenté en dev)
        // app(\App\Services\SmsService::class)->envoyerOtp($admin->telephone, $otpCode);

        // En développement : log le code dans le fichier de log Laravel
        Log::channel('admin')->info('Code 2FA Super Admin pour ' . $admin->email . ' : ' . $otpCode);
// // Après
// $smsSent = app(\App\Services\SmsService::class)->envoyerOtp($admin->telephone, $otpCode);

// // Garder le log en parallèle (utile pour debug même en prod)
// Log::channel('admin')->info('Code 2FA Super Admin pour ' . $admin->email . ' : ' . $otpCode . ($smsSent ? ' [SMS envoyé]' : ' [SMS ECHEC]'));
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
        $admin         = Admin::findOrFail($adminId);
        $hashedOtp     = Cache::get('2fa_admin_' . $admin->id);

        if (! $hashedOtp || ! Hash::check($request->code, $hashedOtp)) {
            AuditLog::enregistrer(
                $admin,
                'LOGIN_2FA_ECHEC',
                'Code 2FA incorrect.',
                $request,
                'WARNING'
            );

            return back()->withErrors(['code' => 'Code incorrect ou expiré.']);
        }

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
}