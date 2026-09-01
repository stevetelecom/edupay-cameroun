<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\PasswordResetCode;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouveau payeur (parent / élève / étudiant).
     * Retourne un token Sanctum + l'utilisateur, prêt à être utilisé.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'profil'     => $validated['profil'],
            'prenom'     => $validated['prenom'],
            'nom'        => $validated['nom'],
            'name'       => $validated['prenom'] . ' ' . $validated['nom'],
            'telephone'  => $validated['telephone'],
            'email'      => $validated['email'] ?? null,
            'ville'      => $validated['ville'],
            'quartier'   => $validated['quartier'] ?? null,
            'password'   => Hash::make($validated['password']),
            'notif_sms'  => $validated['notif_sms'] ?? true,
            'notif_email' => $validated['notif_email'] ?? true,
            'notif_rappel_echeance' => $validated['notif_rappel_echeance'] ?? true,
            'suspendu'   => false,
        ]);

        $role = match ($validated['profil']) {
            'eleve', 'etudiant' => 'eleve',
            default             => 'parent',
        };
        $user->assignRole($role);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * Connexion — login = email OU téléphone (9 chiffres 6XXXXXXXX).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $login       = $credentials['login'];

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';
        $user  = User::where($field, $login)->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Identifiants invalides.',
                'errors'  => ['login' => ['Email ou téléphone ou mot de passe incorrect.']],
            ], 401);
        }

        if ($user->suspendu) {
            return response()->json([
                'message' => 'Ce compte est suspendu. Contactez le support.',
            ], 403);
        }

        // Miroir web LoginController : blocage systématique des comptes d'établissement
        // tant que l'établissement n'est pas actif.
        if ($user->hasAnyRole(['directeur', 'comptable', 'caissier'])) {
            $etablissement = $user->etablissement;
            if ($etablissement && $etablissement->statut !== 'actif') {
                $message = match ($etablissement->statut) {
                    'en_attente' => 'Votre dossier est en cours d\'examen par l\'équipe EduPay. Vous serez notifié(e) par email dès activation à l\'adresse ' . $user->email . '.',
                    'suspendu'   => 'Votre établissement a été suspendu. Contactez le support EduPay pour plus d\'informations.',
                    default      => 'Votre compte établissement n\'est pas encore actif.',
                };
                return response()->json(['message' => $message], 403);
            }
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * Déconnexion — révoque le token courant.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Envoi d'un code de réinitialisation (anti-énumération : réponse identique
     * que le compte existe ou non).
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        $user = User::where('email', $email)->first();

        // Anti-énumération : on ne révèle jamais si le compte existe.
        $reponse = [
            'message' => 'Si un compte existe avec cette adresse email, un code de vérification vient de lui être envoyé.',
        ];

        if (! $user) {
            return response()->json($reponse);
        }

        try {
            PasswordReset::forEmail($email, 'web')->where('is_verified', false)->delete();

            $codeClair = PasswordReset::creerPour($email, 'web');

            Mail::to($email)->send(new PasswordResetCode(
                code:       $codeClair,
                userName:   $user->prenom ?? 'Utilisateur',
                expiresIn:  '15 minutes',
            ));
        } catch (\Throwable $e) {
            Log::error('API forgotPassword : échec d\'envoi du code : ' . $e->getMessage());
        }

        return response()->json($reponse);
    }

    /**
     * Réinitialisation du mot de passe avec le code reçu par email.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $resetRecord = PasswordReset::verifierCode($validated['email'], $validated['code']);

        if (! $resetRecord) {
            return response()->json([
                'message' => 'Code invalide, expiré, ou nombre maximal de tentatives atteint.',
                'errors'  => ['code' => ['Code invalide ou expiré.']],
            ], 422);
        }

        $resetRecord->markAsVerified();

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'Compte introuvable.'], 422);
        }

        if (Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
                'errors'  => ['password' => ['Choisissez un mot de passe différent de l\'ancien.']],
            ], 422);
        }

        try {
            DB::transaction(function () use ($user, $validated, $resetRecord) {
                $user->update(['password' => $validated['password']]);
                PasswordReset::forEmail($validated['email'], $resetRecord->guard)->delete();
                // Révoquer les anciens tokens de l'appareil mobile pour forcer reconnexion
                $user->tokens()->delete();
            });

            return response()->json(['message' => 'Votre mot de passe a été réinitialisé. Veuillez vous reconnecter.']);
        } catch (\Throwable $e) {
            Log::error('API resetPassword : erreur : ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la réinitialisation du mot de passe.'], 500);
        }
    }

    /**
     * Profil de l'utilisateur connecté.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * OTP par email — envoi du code de vérification.
     *
     * POST /api/v1/auth/otp { login: "email ou telephone" }
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate(['login' => 'required|string']);

        $login = $request->login;

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $login)->first();
        } else {
            $telephone = preg_replace('/[\s\-\+]/', '', $login);
            if (strlen($telephone) > 9) {
                $telephone = substr($telephone, -9);
            }
            $user = User::where('telephone', $telephone)->first();
        }

        if (! $user) {
            return response()->json([
                'message' => 'Aucun compte trouvé.',
            ], 404);
        }

        if ($user->suspendu) {
            return response()->json([
                'message' => 'Votre compte a été suspendu. Contactez le support.',
            ], 403);
        }

        if (! $user->email) {
            return response()->json([
                'message' => 'Aucune adresse email associée à ce compte. Utilisez la connexion par mot de passe.',
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);
        $key = 'otp_api_' . $user->id;
        Cache::put($key, Hash::make($otp), now()->addMinutes(5));

        try {
            Mail::to($user->email)->send(new \App\Mail\ParentOtpMail($user, $otp));
            Log::info("OTP API envoyé par email à {$login}");
        } catch (\Throwable $e) {
            Cache::forget($key);
            Log::error('API OTP : échec envoi email : ' . $e->getMessage());
            return response()->json([
                'message' => 'Impossible d\'envoyer le code par email. Veuillez réessayer.',
            ], 500);
        }

        return response()->json([
            'message' => "Un code de vérification a été envoyé à votre adresse email.",
            'email_masked' => substr($user->email, 0, 2) . str_repeat('*', max(0, strlen($user->email) - 6)) . substr($user->email, -4),
        ]);
    }

    /**
     * OTP par email — vérification du code et connexion.
     *
     * POST /api/v1/auth/otp/verify { login: "email ou telephone", otp_code: "123456" }
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'login'     => 'required|string',
            'otp_code'  => 'required|string|digits:6',
        ]);

        $login = $request->login;

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $login)->first();
        } else {
            $telephone = preg_replace('/[\s\-\+]/', '', $login);
            if (strlen($telephone) > 9) {
                $telephone = substr($telephone, -9);
            }
            $user = User::where('telephone', $telephone)->first();
        }

        if (! $user) {
            return response()->json([
                'message' => 'Code invalide ou expiré.',
            ], 401);
        }

        $otpAttemptsKey = 'otp_api_attempts_' . $request->ip() . '_' . $user->id;
        $attempts = Cache::get($otpAttemptsKey, 0);

        if ($attempts >= 5) {
            Cache::forget('otp_api_' . $user->id);
            Cache::forget($otpAttemptsKey);
            return response()->json([
                'message' => 'Trop de tentatives. Veuillez demander un nouveau code.',
            ], 429);
        }

        $hashedOtp = Cache::get('otp_api_' . $user->id);

        if (! $hashedOtp || ! Hash::check($request->otp_code, $hashedOtp)) {
            Cache::put($otpAttemptsKey, $attempts + 1, now()->addMinutes(10));
            return response()->json([
                'message' => 'Code invalide ou expiré.',
            ], 401);
        }

        Cache::forget('otp_api_' . $user->id);
        Cache::forget($otpAttemptsKey);

        if ($user->suspendu) {
            return response()->json([
                'message' => 'Votre compte a été suspendu. Contactez le support.',
            ], 403);
        }

        $token = $user->createToken('mobile-otp')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }
}
