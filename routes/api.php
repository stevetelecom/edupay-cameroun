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
        // Throttles : ces 5 routes n'en avaient aucun (seuls /otp et
        // /otp/verify etaient limites). Sans limite, le brute-force du login
        // et l'envoi massif de codes de reinitialisation n'etaient pas
        // freines, et `/inscription-etablissement` ne l'etait pas
        // bridee non plus.
        Route::post('/register',            [\App\Http\Controllers\Api\AuthController::class, 'register'])->middleware('throttle:5,1')->name('api.v1.auth.register');
        Route::post('/inscription-etablissement', [\App\Http\Controllers\Api\InscriptionEtablissementController::class, 'store'])->middleware('throttle:5,1')->name('api.v1.auth.inscription-etablissement');
        Route::post('/login',               [\App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:10,1')->name('api.v1.auth.login');
        Route::post('/forgot-password',     [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword'])->middleware('throttle:3,1')->name('api.v1.auth.forgot');
        Route::post('/reset-password',      [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('api.v1.auth.reset');

        // OTP par email (connexion sans mot de passe)
        Route::post('/otp',            [\App\Http\Controllers\Api\AuthController::class, 'sendOtp'])->middleware('throttle:10,1')->name('api.v1.auth.otp');
        Route::post('/otp/verify',     [\App\Http\Controllers\Api\AuthController::class, 'verifyOtp'])->middleware('throttle:15,1')->name('api.v1.auth.otp.verify');

        // Authentifié
        // La deconnexion n'est PAS soumise a CompteSuspendu : un compte
        // suspendu doit pouvoir tout de même fermer sa session.
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('api.v1.auth.logout');
    });

    // ── Contact public (formulaire de contact → email support) ───
    Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'submit'])
        ->middleware('throttle:3,1')
        ->name('api.v1.contact.submit');

    // ── Public : stats globales + détail établissement (équivalent landing) ──
    Route::get('/stats', [\App\Http\Controllers\Api\EtablissementPublicController::class, 'stats'])
        ->middleware('throttle:60,1')
        ->name('api.v1.stats');
    Route::get('/etablissements/{code}', [\App\Http\Controllers\Api\EtablissementPublicController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('api.v1.etablissements.show');

    // ── Routes protégées (token Sanctum) ───────────────────────
    Route::middleware(['auth:sanctum', \App\Http\Middleware\CompteSuspendu::class])->group(function () {

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
        // Recherche d'un enfant a rattacher : une requete = un LIKE par mot
        // sur toute la table des apprenants, sans aucune limite de debit.
        Route::get('/apprenants/search',            [\App\Http\Controllers\Api\ApprenantController::class, 'searchApprenants'])->middleware('throttle:30,1')->name('api.v1.apprenants.search');
        Route::post('/apprenants/rattacher',        [\App\Http\Controllers\Api\ApprenantController::class, 'rattacher'])->name('api.v1.apprenants.rattacher');
        Route::put('/apprenants/{apprenant}',       [\App\Http\Controllers\Api\ApprenantController::class, 'updateInfo'])->name('api.v1.apprenants.update');
        Route::delete('/apprenants/{apprenant}',    [\App\Http\Controllers\Api\ApprenantController::class, 'detacher'])->name('api.v1.apprenants.detacher');

        // Frais
        Route::get('/frais/{apprenant}', [\App\Http\Controllers\Api\FraisController::class, 'index'])->name('api.v1.frais.index');
        Route::get('/frais-apprenants/{frais_apprenant}', [\App\Http\Controllers\Api\FraisController::class, 'show'])->name('api.v1.frais-apprenants.show');

        // Paiements
        //
        // Ces trois routes sont les seules qui appellent AangaraaPay depuis
        // l'API, et `verifier` le fait a CHAQUE poll : sans limite, un seul
        // compte authentique pouvait déclencher des dizaines de milliers
        // d'appels sortants par jour avec notre app_key, et faire bannir
        // l'app_key chez le prestataire. Les routes /auth avaient déjà un
        // throttle, celles-ci avaient été oubliées.
        //
        // Les valeurs laisse une marge tres large pour l'usage reel : le web
        // interroge toutes les 5 s pendant ~20 min, soit 156 appels, ~12/min
        // en pointe (paiement_attente.blade.php:144-146). 120/min couvre
        // plusieurs paiements simultanes. Et meme en cas de 429, aucun argent
        // n'est perdu : `aangaraa:reconcilie` tourne toutes les 2 min et
        // solde les paiements confirmes cote serveur, independamment du
        // client. Le polling est un confort, jamais la source de verite.
        Route::get('/paiements',                [\App\Http\Controllers\Api\PaiementController::class, 'index'])->name('api.v1.paiements.index');
        Route::post('/paiements/initier',       [\App\Http\Controllers\Api\PaiementController::class, 'initier'])->middleware('throttle:10,1')->name('api.v1.paiements.initier');
        Route::post('/paiements/{paiement}/verifier', [\App\Http\Controllers\Api\PaiementController::class, 'verifier'])->middleware('throttle:120,1')->name('api.v1.paiements.verifier');
        Route::post('/paiements/{paiement}/annuler',  [\App\Http\Controllers\Api\PaiementController::class, 'annuler'])->middleware('throttle:20,1')->name('api.v1.paiements.annuler');

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
        Route::get('/paiements/export',          [\App\Http\Controllers\Api\DocumentPayeurController::class, 'exporterHistorique'])->name('api.v1.paiements.export');
        Route::get('/paiements/{paiement}/recu',        [\App\Http\Controllers\Api\DocumentPayeurController::class, 'telechargerRecu'])->name('api.v1.paiements.recu');
        Route::get('/apprenants/{apprenant}/certificat', [\App\Http\Controllers\Api\DocumentPayeurController::class, 'genererCertificat'])->name('api.v1.apprenants.certificat');
    });

    // ── Back-office Établissement (directeur / comptable / caissier) ──
    Route::prefix('etablissement')->middleware(['auth:sanctum', \App\Http\Middleware\CompteSuspendu::class, 'check.abonnement'])->group(function () {
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
        Route::post('/frais/{frais}/dupliquer',            [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'dupliquer'])->name('api.v1.etablissement.frais.dupliquer');
        Route::post('/frais/purger-annees-passees',        [\App\Http\Controllers\Api\Etablissement\FraisController::class, 'purgerAnneesPassees'])->name('api.v1.etablissement.frais.purger');

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
