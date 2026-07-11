<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EtablissementAdminController;
use App\Http\Controllers\Admin\PayeurAdminController;
use App\Http\Controllers\Admin\TransactionAdminController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\LogSecuriteController;
use App\Http\Controllers\Admin\ReclamationAdminController;
use App\Http\Controllers\Admin\ParametreSystemeController;
use App\Http\Controllers\Admin\ExportController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Authentification Super Admin
|--------------------------------------------------------------------------
*/
Route::middleware('guest:admin')->group(function () {
    // Register Super Admin — URL cachée, protégée par token secret
    Route::get('/register',  [AdminAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AdminAuthController::class, 'register'])->name('register.post');

    Route::get('/login',          [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login',         [AdminAuthController::class, 'login'])->name('login.post');
    Route::get('/login/2fa',      [AdminAuthController::class, 'show2fa'])->name('login.2fa');
    Route::post('/login/2fa',     [AdminAuthController::class, 'verify2fa'])->name('login.2fa.verify');
    Route::post('/login/2fa/resend', [AdminAuthController::class, 'resend2fa'])->name('login.2fa.resend');
    Route::get('/password/forgot',   [AdminAuthController::class, 'showForgotForm'])->name('password.forgot');
    Route::post('/password/forgot',  [AdminAuthController::class, 'sendResetCode'])->name('password.forgot.send');
    Route::get('/password/reset',    [AdminAuthController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset',   [AdminAuthController::class, 'resetPassword'])->name('password.reset');
});

Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Dashboard & modules Super Admin — auth:admin obligatoire
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin', 'super.admin'])->group(function () {
    // Abonnements
    Route::get('/abonnements', [\App\Http\Controllers\Admin\AbonnementController::class, 'index'])->name('abonnements.index');
    Route::post('/abonnements', [\App\Http\Controllers\Admin\AbonnementController::class, 'store'])->name('abonnements.store');
    Route::patch('/abonnements/{abonnement}/renouveler', [\App\Http\Controllers\Admin\AbonnementController::class, 'renouveler'])->name('abonnements.renouveler');
    Route::patch('/abonnements/{abonnement}', [\App\Http\Controllers\Admin\AbonnementController::class, 'update'])->name('abonnements.update');
    Route::delete('/abonnements/{abonnement}', [\App\Http\Controllers\Admin\AbonnementController::class, 'destroy'])->name('abonnements.destroy');



    // Gestion de l'équipe Super Admin
    Route::prefix('admins')->name('admins.')->group(function () {
        Route::get('/',                    [\App\Http\Controllers\Admin\AdminGestionController::class, 'index'])->name('index');
        Route::post('/',                   [\App\Http\Controllers\Admin\AdminGestionController::class, 'store'])->name('store');
        Route::patch('/{admin}',           [\App\Http\Controllers\Admin\AdminGestionController::class, 'update'])->name('update');
        Route::patch('/{admin}/activer',   [\App\Http\Controllers\Admin\AdminGestionController::class, 'activer'])->name('activer');
        Route::patch('/{admin}/suspendre', [\App\Http\Controllers\Admin\AdminGestionController::class, 'suspendre'])->name('suspendre');
        Route::delete('/{admin}',          [\App\Http\Controllers\Admin\AdminGestionController::class, 'destroy'])->name('destroy');
    });

    // Dashboard KPIs globaux
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/profil', [\App\Http\Controllers\Admin\AdminGestionController::class, 'updateProfil'])->name('profil.update');

    // Gestion des établissements
    Route::prefix('etablissements')->name('etablissements.')->group(function () {
        Route::get('/',                           [EtablissementAdminController::class, 'index'])->name('index');
        Route::get('/datatable',                  [EtablissementAdminController::class, 'datatable'])->name('datatable');
        Route::get('/{etablissement}',            [EtablissementAdminController::class, 'show'])->name('show');
        Route::patch('/{etablissement}/activer',  [EtablissementAdminController::class, 'activer'])->name('activer');
        Route::patch('/{etablissement}/suspendre',[EtablissementAdminController::class, 'suspendre'])->name('suspendre');
        Route::delete('/{etablissement}',         [EtablissementAdminController::class, 'destroy'])->name('destroy');
    });

    // Comptes payeurs (parents / élèves / étudiants)
    Route::prefix('payeurs')->name('payeurs.')->group(function () {
        Route::get('/',                   [PayeurAdminController::class, 'index'])->name('index');
        Route::get('/datatable',          [PayeurAdminController::class, 'datatable'])->name('datatable');
        Route::get('/{payeur}',           [PayeurAdminController::class, 'show'])->name('show');
        Route::patch('/{payeur}/suspendre',[PayeurAdminController::class, 'suspendre'])->name('suspendre');
        Route::patch('/{payeur}/activer', [PayeurAdminController::class, 'activer'])->name('activer');
        Route::delete('/{payeur}',        [PayeurAdminController::class, 'destroy'])->name('destroy');
    });

    // Transactions globales
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/',          [TransactionAdminController::class, 'index'])->name('index');
        Route::get('/{paiement}',[TransactionAdminController::class, 'show'])->name('show');
    });

    // Commissions
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/',                              [CommissionController::class, 'index'])->name('index');
        Route::get('/{etablissement}/modifier',      [CommissionController::class, 'edit'])->name('edit');
        Route::patch('/{etablissement}/modifier',    [CommissionController::class, 'update'])->name('update');
        Route::patch('/{commission}/prelever',       [CommissionController::class, 'marquerPrelevee'])->name('prelever');
    });

    // Reclamations
    Route::prefix('reclamations')->name('reclamations.')->group(function () {
        Route::get('/',                            [ReclamationAdminController::class, 'index'])->name('index');
        Route::get('/{reclamation}',               [ReclamationAdminController::class, 'show'])->name('show');
        Route::patch('/{reclamation}/repondre',    [ReclamationAdminController::class, 'repondre'])->name('repondre');
    });

    // Logs de securite
    Route::prefix('logs-securite')->name('logs.')->group(function () {
        Route::get('/',        [LogSecuriteController::class, 'index'])->name('index');
        Route::get('/{log}',   [LogSecuriteController::class, 'show'])->name('show');
    });

    // Exports reglementaires
    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/',                  [ExportController::class, 'index'])->name('index');
        Route::get('/rapport-mensuel',   [ExportController::class, 'rapportMensuelBeac'])->name('mensuel');
        Route::get('/declaration-cobac', [ExportController::class, 'declarationCobac'])->name('cobac');
    });

    // Parametres systeme
    Route::prefix('parametres')->name('parametres.')->group(function () {
        Route::get('/',        [ParametreSystemeController::class, 'index'])->name('index');
        Route::post('/',       [ParametreSystemeController::class, 'update'])->name('update');
        Route::post('/cache',  [ParametreSystemeController::class, 'viderCache'])->name('cache');
    });
});