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
        Route::post('/paiements/{paiement}/annuler',  [\App\Http\Controllers\Api\PaiementController::class, 'annuler'])->name('api.v1.paiements.annuler');

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

        // Apprenants
        Route::get('/apprenants',              [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'index'])->name('api.v1.etablissement.apprenants.index');
        Route::post('/apprenants',             [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'store'])->name('api.v1.etablissement.apprenants.store');
        Route::get('/apprenants/{apprenant}',  [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'show'])->name('api.v1.etablissement.apprenants.show');
        Route::put('/apprenants/{apprenant}',  [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'update'])->name('api.v1.etablissement.apprenants.update');
        Route::delete('/apprenants/{apprenant}', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'destroy'])->name('api.v1.etablissement.apprenants.destroy');
        Route::post('/apprenants/{apprenant}/valider', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'valider'])->name('api.v1.etablissement.apprenants.valider');
        Route::post('/apprenants/{apprenant}/rejeter', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'rejeter'])->name('api.v1.etablissement.apprenants.rejeter');

        // Impayés
        Route::get('/impayes', [\App\Http\Controllers\Api\Etablissement\ImpayeController::class, 'index'])->name('api.v1.etablissement.impayes.index');
        Route::post('/impayes/relancer', [\App\Http\Controllers\Api\Etablissement\ImpayeController::class, 'relancerSms'])->name('api.v1.etablissement.impayes.relancer');
        Route::post('/impayes/apprenants/{apprenant}/relancer', [\App\Http\Controllers\Api\Etablissement\ImpayeController::class, 'relancerApprenant'])->name('api.v1.etablissement.impayes.relancerApprenant');

        // Rapports
        Route::get('/rapports', [\App\Http\Controllers\Api\Etablissement\RapportController::class, 'index'])->name('api.v1.etablissement.rapports.index');

        // Catégories de frais & échéanciers
        Route::get('/frais',                               [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'index'])->name('api.v1.etablissement.frais.index');
        Route::post('/frais',                              [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'store'])->name('api.v1.etablissement.frais.store');
        Route::put('/frais/{frais}',                       [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'update'])->name('api.v1.etablissement.frais.update');
        Route::delete('/frais/{frais}',                    [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'destroy'])->name('api.v1.etablissement.frais.destroy');
        Route::post('/frais/{frais}/affecter',             [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'affecter'])->name('api.v1.etablissement.frais.affecter');
        Route::post('/frais/{frais}/echeanciers',          [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'storeEcheancier'])->name('api.v1.etablissement.frais.echeanciers.store');
        Route::delete('/frais/{frais}/echeanciers/{echeancier}', [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'destroyEcheancier'])->name('api.v1.etablissement.frais.echeanciers.destroy');

        // Historique des paiements
        Route::get('/paiements', [\App\Http\Controllers\Api\Etablissement\PaiementController::class, 'index'])->name('api.v1.etablissement.paiements.index');

        // Utilisateurs internes (directeur/comptable/caissier)
        Route::get('/utilisateurs',                                [\App\Http\Controllers\Api\Etablissement\UtilisateurController::class, 'index'])->name('api.v1.etablissement.utilisateurs.index');
        Route::post('/utilisateurs',                               [\App\Http\Controllers\Api\Etablissement\UtilisateurController::class, 'store'])->name('api.v1.etablissement.utilisateurs.store');
        Route::put('/utilisateurs/{utilisateur}/role',             [\App\Http\Controllers\Api\Etablissement\UtilisateurController::class, 'updateRole'])->name('api.v1.etablissement.utilisateurs.role');
        Route::delete('/utilisateurs/{utilisateur}',               [\App\Http\Controllers\Api\Etablissement\UtilisateurController::class, 'destroy'])->name('api.v1.etablissement.utilisateurs.destroy');

        // Remboursements
        Route::get('/remboursements',                              [\App\Http\Controllers\Api\Etablissement\RemboursementController::class, 'index'])->name('api.v1.etablissement.remboursements.index');
        Route::post('/remboursements',                             [\App\Http\Controllers\Api\Etablissement\RemboursementController::class, 'store'])->name('api.v1.etablissement.remboursements.store');
        Route::post('/remboursements/{remboursement}/approuver',   [\App\Http\Controllers\Api\Etablissement\RemboursementController::class, 'approuver'])->name('api.v1.etablissement.remboursements.approuver');
        Route::post('/remboursements/{remboursement}/refuser',     [\App\Http\Controllers\Api\Etablissement\RemboursementController::class, 'refuser'])->name('api.v1.etablissement.remboursements.refuser');
    });
});
