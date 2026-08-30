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
            'telephone'  => $validated['telephone'],
            'email'      => $validated['email'] ?? null,
            'ville'      => $validated['ville'],
            'quartier'   => $validated['quartier'] ?? null,
            'password'   => $validated['password'],
            'notif_sms'  => $validated['notif_sms'] ?? true,
            'notif_email' => $validated['notif_email'] ?? true,
            'notif_rappel_echeance' => $validated['notif_rappel_echeance'] ?? true,
            'suspendu'   => false,
        ]);

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

        if (! $user->hasVerifiedEmail()) {
            // L'email est optionnel à l'inscription (pas toujours vérifié) — on laisse passer.
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
}
