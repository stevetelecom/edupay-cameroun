<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Models\User;
use App\Models\Admin;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    // Message volontairement identique, que le compte existe ou non (anti-énumération)
    private const MESSAGE_GENERIQUE = 'Si un compte existe avec cette adresse email, un code de vérification vient de lui être envoyé.';

    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        $this->genererEtEnvoyerCode($email);

        // 🔒 Réponse strictement identique que le compte existe ou non
        return redirect()->route('password.verify.form', ['email' => $email])
            ->with('success', self::MESSAGE_GENERIQUE);
    }

    public function showVerifyForm(Request $request): View
    {
        $email = $request->query('email');
        if (!$email) {
            return redirect()->route('password.forgot');
        }
        return view('auth.verify-code', ['email' => $email]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $resetRecord = PasswordReset::verifierCode($request->email, $request->code);

        if (! $resetRecord) {
            return back()->withErrors([
                'code' => 'Code invalide, expiré, ou nombre maximal de tentatives atteint. Demandez un nouveau code.',
            ])->withInput();
        }

        $resetRecord->markAsVerified();

        return redirect()->route('password.reset.form', [
            'email' => $request->email,
            'token' => $resetRecord->id,
        ])->with('success', 'Code vérifié avec succès.');
    }

    public function showResetForm(Request $request): View
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email || !$token) {
            return redirect()->route('password.forgot');
        }

        $resetRecord = PasswordReset::find($token);

        if (! $resetRecord || $resetRecord->email !== $email || !$resetRecord->is_verified) {
            return redirect()->route('password.forgot')->withErrors([
                'error' => 'Lien invalide ou expiré.',
            ]);
        }

        return view('auth.reset-password', ['email' => $email, 'token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|numeric',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = $request->email;
        $resetRecord = PasswordReset::find($request->token);

        if (! $resetRecord || $resetRecord->email !== $email || !$resetRecord->is_verified) {
            return back()->withErrors(['error' => 'Lien invalide ou expiré.']);
        }

        $user = $resetRecord->guard === 'web'
            ? User::where('email', $email)->first()
            : Admin::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors(['error' => 'Lien invalide ou expiré.']);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
            ]);
        }

        try {
            DB::transaction(function () use ($user, $email, $resetRecord, $request) {
                $user->update(['password' => $request->password]);
                PasswordReset::forEmail($email, $resetRecord->guard)->delete();
            });

            return redirect()->route('login')
                ->with('success', 'Votre mot de passe a été réinitialisé avec succès. Veuillez vous connecter.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erreur lors de la réinitialisation du mot de passe.']);
        }
    }

    public function resendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        $this->genererEtEnvoyerCode($email);

        // 🔒 Réponse strictement identique que le compte existe ou non
        return back()->with('success', self::MESSAGE_GENERIQUE);
    }

    /**
     * Génère et envoie un code si un compte existe pour cet email.
     * Ne révèle jamais si le compte existe (appelant reste silencieux dans tous les cas).
     * Ne logge JAMAIS le code en clair.
     */
    private function genererEtEnvoyerCode(string $email): void
    {
        $userRecord  = User::where('email', $email)->first();
        $adminRecord = Admin::where('email', $email)->first();

        if (! $userRecord && ! $adminRecord) {
            // Rien à faire, mais on ne le dit jamais à l'appelant (anti-énumération)
            return;
        }

        $guard = $userRecord ? 'web' : 'admin';
        $user  = $userRecord ?? $adminRecord;

        // Nettoyer les anciens codes non vérifiés
        PasswordReset::forEmail($email, $guard)->where('is_verified', false)->delete();

        // Génère le code, le hash en base, récupère le code en clair uniquement pour l'email
        $codeClair = PasswordReset::creerPour($email, $guard);

        try {
            Mail::to($email)->send(new PasswordResetCode(
                code: $codeClair,
                userName: $user->prenom ?? 'Utilisateur',
                expiresIn: '15 minutes'
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Échec envoi email reset password : ' . $e->getMessage());
        }
    }
}
