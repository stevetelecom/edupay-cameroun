<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST EduPay Cameroun — version 1
|--------------------------------------------------------------------------
| Socle consommé par le frontend mobile (Mobine).
| Authentification : token Sanctum (Bearer).
*/

Route::prefix('v1')->group(function () {

    // ── Authentification publique ──────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register',            [\App\Http\Controllers\Api\AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login',               [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('/forgot-password',     [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot');
        Route::post('/reset-password',      [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->name('api.v1.auth.reset');

        // Authentifié
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('api.v1.auth.logout');
    });

    // ── Routes protégées (token Sanctum) ───────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Profil
        Route::get('/me',     [\App\Http\Controllers\Api\AuthController::class, 'me'])->name('api.v1.me');
        Route::get('/profil', [\App\Http\Controllers\Api\ProfilController::class, 'show'])->name('api.v1.profil.show');
        Route::put('/profil', [\App\Http\Controllers\Api\ProfilController::class, 'update'])->name('api.v1.profil.update');

        // Apprenants / rattachement
        Route::get('/apprenants',                   [\App\Http\Controllers\Api\ApprenantController::class, 'index'])->name('api.v1.apprenants.index');
        Route::post('/apprenants/rattacher',        [\App\Http\Controllers\Api\ApprenantController::class, 'rattacher'])->name('api.v1.apprenants.rattacher');
        Route::delete('/apprenants/{apprenant}',    [\App\Http\Controllers\Api\ApprenantController::class, 'detacher'])->name('api.v1.apprenants.detacher');

        // Frais
        Route::get('/frais/{apprenant}', [\App\Http\Controllers\Api\FraisController::class, 'index'])->name('api.v1.frais.index');

        // Paiements
        Route::get('/paiements',                [\App\Http\Controllers\Api\PaiementController::class, 'index'])->name('api.v1.paiements.index');
        Route::post('/paiements/initier',       [\App\Http\Controllers\Api\PaiementController::class, 'initier'])->name('api.v1.paiements.initier');
        Route::post('/paiements/{paiement}/verifier', [\App\Http\Controllers\Api\PaiementController::class, 'verifier'])->name('api.v1.paiements.verifier');

        // Réclamations
        Route::get('/reclamations',   [\App\Http\Controllers\Api\ReclamationController::class, 'index'])->name('api.v1.reclamations.index');
        Route::post('/reclamations',  [\App\Http\Controllers\Api\ReclamationController::class, 'store'])->name('api.v1.reclamations.store');

        // Notifications
        Route::get('/notifications',       [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::post('/notifications/lire', [\App\Http\Controllers\Api\NotificationController::class, 'lire'])->name('api.v1.notifications.lire');
    });

    // ── Back-office Établissement (directeur / comptable / caissier) ──
    Route::prefix('etablissement')->middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\Etablissement\DashboardController::class, 'index'])->name('api.v1.etablissement.dashboard');
    });
});
