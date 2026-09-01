<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST EduPay Cameroun — version 1
|--------------------------------------------------------------------------
| Socle consommé par le frontend mobile (WanDji Estelle ).
| Authentification : token Sanctum (Bearer).
*/

Route::prefix('v1')->group(function () {

    // ── Authentification publique ──────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register',            [\App\Http\Controllers\Api\AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/inscription-etablissement', [\App\Http\Controllers\Api\InscriptionEtablissementController::class, 'store'])->name('api.v1.auth.inscription-etablissement');
        Route::post('/login',               [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('/forgot-password',     [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot');
        Route::post('/reset-password',      [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->name('api.v1.auth.reset');

        // OTP par email (connexion sans mot de passe)
        Route::post('/otp',            [\App\Http\Controllers\Api\AuthController::class, 'sendOtp'])->middleware('throttle:10,1')->name('api.v1.auth.otp');
        Route::post('/otp/verify',     [\App\Http\Controllers\Api\AuthController::class, 'verifyOtp'])->middleware('throttle:15,1')->name('api.v1.auth.otp.verify');

        // Authentifié
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('api.v1.auth.logout');
    });

    // ── Contact public (formulaire de contact → email support) ───
    Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'submit'])
        ->name('api.v1.contact.submit');

    // ── Public : stats globales + détail établissement (équivalent landing) ──
    Route::get('/stats', [\App\Http\Controllers\Api\EtablissementPublicController::class, 'stats'])
        ->name('api.v1.stats');
    Route::get('/etablissements/{code}', [\App\Http\Controllers\Api\EtablissementPublicController::class, 'show'])
        ->name('api.v1.etablissements.show');

    // ── Routes protégées (token Sanctum) ───────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Profil
        Route::get('/me',     [\App\Http\Controllers\Api\AuthController::class, 'me'])->name('api.v1.me');
        Route::get('/profil', [\App\Http\Controllers\Api\ProfilController::class, 'show'])->name('api.v1.profil.show');
        Route::put('/profil', [\App\Http\Controllers\Api\ProfilController::class, 'update'])->name('api.v1.profil.update');
        Route::put('/profil/notifications', [\App\Http\Controllers\Api\ProfilController::class, 'updateNotifications'])->name('api.v1.profil.notifications');
        Route::put('/profil/password',       [\App\Http\Controllers\Api\ProfilController::class, 'updatePassword'])->name('api.v1.profil.password');

        // Apprenants / rattachement
        Route::get('/apprenants',                   [\App\Http\Controllers\Api\ApprenantController::class, 'index'])->name('api.v1.apprenants.index');
        Route::get('/apprenants/mes-enfants',       [\App\Http\Controllers\Api\ApprenantController::class, 'mesEnfants'])->name('api.v1.apprenants.mesEnfants');
        Route::get('/apprenants/etablissements',    [\App\Http\Controllers\Api\ApprenantController::class, 'etablissements'])->name('api.v1.apprenants.etablissements');
        Route::post('/apprenants/rattacher',        [\App\Http\Controllers\Api\ApprenantController::class, 'rattacher'])->name('api.v1.apprenants.rattacher');
        Route::put('/apprenants/{apprenant}',       [\App\Http\Controllers\Api\ApprenantController::class, 'updateInfo'])->name('api.v1.apprenants.update');
        Route::delete('/apprenants/{apprenant}',    [\App\Http\Controllers\Api\ApprenantController::class, 'detacher'])->name('api.v1.apprenants.detacher');

        // Frais
        Route::get('/frais/{apprenant}', [\App\Http\Controllers\Api\FraisController::class, 'index'])->name('api.v1.frais.index');
        Route::get('/frais-apprenants/{frais_apprenant}', [\App\Http\Controllers\Api\FraisController::class, 'show'])->name('api.v1.frais-apprenants.show');

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

        // Dashboard payeur
        Route::get('/dashboard',              [\App\Http\Controllers\Api\DashboardController::class, 'index'])->name('api.v1.dashboard');
        Route::post('/notifications/{notification}/lue', [\App\Http\Controllers\Api\DashboardController::class, 'marquerNotificationLue'])->name('api.v1.notifications.lue');

        // Documents PDF (reçus & certificats)
        Route::get('/paiements/{paiement}/recu',        [\App\Http\Controllers\Api\DocumentPayeurController::class, 'telechargerRecu'])->name('api.v1.paiements.recu');
        Route::get('/apprenants/{apprenant}/certificat', [\App\Http\Controllers\Api\DocumentPayeurController::class, 'genererCertificat'])->name('api.v1.apprenants.certificat');
    });

    // ── Back-office Établissement (directeur / comptable / caissier) ──
    Route::prefix('etablissement')->middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\Etablissement\DashboardController::class, 'index'])->name('api.v1.etablissement.dashboard');

        // Apprenants
        Route::get('/apprenants',              [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'index'])->name('api.v1.etablissement.apprenants.index');
        Route::post('/apprenants',             [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'store'])->name('api.v1.etablissement.apprenants.store');
        Route::get('/apprenants/import/model',  [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'importTemplate'])->name('api.v1.etablissement.apprenants.importModel');
        Route::post('/apprenants/import',       [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'import'])->name('api.v1.etablissement.apprenants.import');
        Route::post('/apprenants/bulk-destroy', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'bulkDestroy'])->name('api.v1.etablissement.apprenants.bulkDestroy');
        Route::get('/apprenants/{apprenant}',  [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'show'])->name('api.v1.etablissement.apprenants.show');
        Route::put('/apprenants/{apprenant}',  [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'update'])->name('api.v1.etablissement.apprenants.update');
        Route::delete('/apprenants/{apprenant}', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'destroy'])->name('api.v1.etablissement.apprenants.destroy');
        Route::delete('/apprenants/{apprenant}/frais/{fraisApprenant}', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'desaffecter'])->name('api.v1.etablissement.apprenants.desaffecter');
        Route::post('/apprenants/{apprenant}/valider', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'valider'])->name('api.v1.etablissement.apprenants.valider');
        Route::post('/apprenants/{apprenant}/rejeter', [\App\Http\Controllers\Api\Etablissement\ApprenantController::class, 'rejeter'])->name('api.v1.etablissement.apprenants.rejeter');

        // Impayés
        Route::get('/impayes', [\App\Http\Controllers\Api\Etablissement\ImpayeController::class, 'index'])->name('api.v1.etablissement.impayes.index');
        Route::post('/impayes/relancer', [\App\Http\Controllers\Api\Etablissement\ImpayeController::class, 'relancerSms'])->name('api.v1.etablissement.impayes.relancer');
        Route::post('/impayes/apprenants/{apprenant}/relancer', [\App\Http\Controllers\Api\Etablissement\ImpayeController::class, 'relancerApprenant'])->name('api.v1.etablissement.impayes.relancerApprenant');

        // Rapports
        Route::get('/rapports',             [\App\Http\Controllers\Api\Etablissement\RapportController::class, 'index'])->name('api.v1.etablissement.rapports.index');
        Route::get('/rapports/export/pdf',  [\App\Http\Controllers\Api\Etablissement\RapportController::class, 'exportPdf'])->name('api.v1.etablissement.rapports.exportPdf');
        Route::get('/rapports/export/excel', [\App\Http\Controllers\Api\Etablissement\RapportController::class, 'exportExcel'])->name('api.v1.etablissement.rapports.exportExcel');

        // Catégories de frais & échéanciers
        Route::get('/frais',                               [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'index'])->name('api.v1.etablissement.frais.index');
        Route::post('/frais',                              [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'store'])->name('api.v1.etablissement.frais.store');
        Route::get('/frais/{frais}',                       [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'show'])->name('api.v1.etablissement.frais.show');
        Route::put('/frais/{frais}',                       [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'update'])->name('api.v1.etablissement.frais.update');
        Route::delete('/frais/{frais}',                    [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'destroy'])->name('api.v1.etablissement.frais.destroy');
        Route::post('/frais/{frais}/affecter',             [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'affecter'])->name('api.v1.etablissement.frais.affecter');
        Route::post('/frais/{frais}/echeanciers',          [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'storeEcheancier'])->name('api.v1.etablissement.frais.echeanciers.store');
        Route::put('/frais/{frais}/echeanciers/{echeancier}', [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'updateEcheancier'])->name('api.v1.etablissement.frais.echeanciers.update');
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

        // Abonnement
        Route::get('/abonnement', [\App\Http\Controllers\Api\Etablissement\DashboardController::class, 'abonnement'])->name('api.v1.etablissement.abonnement');

        // Profil & paramètres de l'établissement
        Route::get('/profil',              [\App\Http\Controllers\Api\Etablissement\ProfilController::class, 'index'])->name('api.v1.etablissement.profil');
        Route::put('/profil',              [\App\Http\Controllers\Api\Etablissement\ProfilController::class, 'updateInfos'])->name('api.v1.etablissement.profil.update');
        Route::put('/profil/password',     [\App\Http\Controllers\Api\Etablissement\ProfilController::class, 'updatePassword'])->name('api.v1.etablissement.profil.password');
        Route::get('/parametres',          [\App\Http\Controllers\Api\Etablissement\ParametreController::class, 'index'])->name('api.v1.etablissement.parametres');
        Route::put('/parametres',          [\App\Http\Controllers\Api\Etablissement\ParametreController::class, 'update'])->name('api.v1.etablissement.parametres.update');

        // Sites (multi-sites)
        Route::get('/sites',     [\App\Http\Controllers\Api\Etablissement\SiteController::class, 'index'])->name('api.v1.etablissement.sites.index');
        Route::post('/sites',    [\App\Http\Controllers\Api\Etablissement\SiteController::class, 'store'])->name('api.v1.etablissement.sites.store');
        Route::put('/sites/{site}',   [\App\Http\Controllers\Api\Etablissement\SiteController::class, 'update'])->name('api.v1.etablissement.sites.update');
        Route::delete('/sites/{site}', [\App\Http\Controllers\Api\Etablissement\SiteController::class, 'destroy'])->name('api.v1.etablissement.sites.destroy');
    });
});
