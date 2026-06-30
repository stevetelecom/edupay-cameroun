<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Models\User;
use App\Models\Admin;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Afficher le formulaire pour demander la réinitialisation
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Envoyer le code de vérification par email
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        // Chercher l'utilisateur dans les deux tables (User et Admin)
        $userRecord = User::where('email', $email)->first();
        $adminRecord = Admin::where('email', $email)->first();

        if (!$userRecord && !$adminRecord) {
            return back()->withErrors([
                'email' => 'Aucun compte trouvé avec cette adresse email.',
            ])->withInput();
        }

        // Déterminer le guard (web = User, admin = Admin)
        $guard = $userRecord ? 'web' : 'admin';
        $user = $userRecord ?? $adminRecord;

        // Nettoyer les codes précédents non vérifiés et expirés
        PasswordReset::forEmail($email, $guard)->where('is_verified', false)->delete();

        // Générer un nouveau code
        $code = PasswordReset::generateUniqueCode();

        // Créer l'enregistrement
        PasswordReset::create([
            'email' => $email,
            'guard' => $guard,
            'code' => $code,
        ]);

        // Pour test local, on écrit le code dans le log sans toucher à Mailtrap
        Log::info("Password reset code for {$email}: {$code}");

        // Envoyer l'email avec le code (Mailtrap / opérateur réel)
        try {
            Mail::to($email)->send(new PasswordResetCode(
                code: $code,
                userName: $user->prenom ?? 'Utilisateur',
                expiresIn: '15 minutes'
            ));

            return redirect()->route('password.verify.form', ['email' => $email])
                ->with('success', 'Un code de vérification a été envoyé à votre adresse email.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Erreur lors de l\'envoi du code. Veuillez réessayer.',
            ])->withInput();
        }
    }

    /**
     * Afficher le formulaire de vérification du code
     */
    public function showVerifyForm(Request $request): View
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('password.forgot');
        }

        return view('auth.verify-code', ['email' => $email]);
    }

    /**
     * Vérifier le code entré par l'utilisateur
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $email = $request->email;
        $code = $request->code;

        // Chercher un code valide et non expiré
        $resetRecord = PasswordReset::forEmail($email, 'web')
            ->orWhere(function ($q) use ($email) {
                $q->forEmail($email, 'admin');
            })
            ->pending()
            ->where('code', $code)
            ->first();

        if (!$resetRecord) {
            return back()->withErrors([
                'code' => 'Code invalide ou expiré. Veuillez réessayer.',
            ])->withInput();
        }

        // Marquer comme vérifié
        $resetRecord->markAsVerified();

        // Rediriger vers le formulaire de reset du mot de passe
        return redirect()->route('password.reset.form', [
            'email' => $email,
            'token' => $resetRecord->id,
        ])->with('success', 'Code vérifié avec succès.');
    }

    /**
     * Afficher le formulaire de réinitialisation du mot de passe
     */
    public function showResetForm(Request $request): View
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (!$email || !$token) {
            return redirect()->route('password.forgot');
        }

        // Vérifier que le token est valide et vérifié
        $resetRecord = PasswordReset::findOrFail($token);

        if ($resetRecord->email !== $email || !$resetRecord->is_verified) {
            return redirect()->route('password.forgot')->withErrors([
                'error' => 'Lien invalide ou expiré.',
            ]);
        }

        return view('auth.reset-password', [
            'email' => $email,
            'token' => $token,
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|numeric',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = $request->email;
        $token = $request->token;

        // Récupérer le record de réinitialisation
        $resetRecord = PasswordReset::findOrFail($token);

        if ($resetRecord->email !== $email || !$resetRecord->is_verified) {
            return back()->withErrors([
                'error' => 'Lien invalide ou expiré.',
            ]);
        }

        // Chercher l'utilisateur (User ou Admin)
        if ($resetRecord->guard === 'web') {
            $user = User::where('email', $email)->firstOrFail();
        } else {
            $user = Admin::where('email', $email)->firstOrFail();
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
            ]);
        }

        // Mettre à jour le mot de passe
        try {
            DB::transaction(function () use ($user, $email, $resetRecord, $request) {
                $user->update(['password' => $request->password]);

                // Nettoyer tous les codes de réinitialisation de cet utilisateur
                PasswordReset::forEmail($email, $resetRecord->guard)->delete();
            });

            return redirect()->route('login')
                ->with('success', 'Votre mot de passe a été réinitialisé avec succès. Veuillez vous connecter.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Erreur lors de la réinitialisation du mot de passe.',
            ]);
        }
    }

    /**
     * Renvoyer le code (l'utilisateur peut demander un nouveau code)
     */
    public function resendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        // Chercher l'utilisateur
        $userRecord = User::where('email', $email)->first();
        $adminRecord = Admin::where('email', $email)->first();

        if (!$userRecord && !$adminRecord) {
            return back()->withErrors([
                'email' => 'Aucun compte trouvé avec cette adresse email.',
            ])->withInput();
        }

        $guard = $userRecord ? 'web' : 'admin';
        $user = $userRecord ?? $adminRecord;

        // Nettoyer les codes précédents non vérifiés
        PasswordReset::forEmail($email, $guard)->where('is_verified', false)->delete();

        // Générer un nouveau code
        $code = PasswordReset::generateUniqueCode();

        PasswordReset::create([
            'email' => $email,
            'guard' => $guard,
            'code' => $code,
        ]);

        // Pour test local, on écrit le code dans le log sans toucher à Mailtrap
        Log::info("Password reset code for {$email}: {$code}");

        try {
            Mail::to($email)->send(new PasswordResetCode(
                code: $code,
                userName: $user->prenom ?? 'Utilisateur',
                expiresIn: '15 minutes'
            ));

            return back()->with('success', 'Un nouveau code a été envoyé à votre adresse email.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Erreur lors de l\'envoi du code.',
            ]);
        }
    }
}
