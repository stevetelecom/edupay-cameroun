<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EtablissementAdminController;
use App\Http\Controllers\Admin\TransactionAdminController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\LogSecuriteController;
use App\Http\Controllers\Admin\ParametreSystemeController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Authentification Super Admin
|--------------------------------------------------------------------------
*/
Route::middleware('guest:admin')->group(function () {
    Route::get('/login',          [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login',         [AdminAuthController::class, 'login'])->name('login.post');
    Route::get('/login/2fa',      [AdminAuthController::class, 'show2fa'])->name('login.2fa');
    Route::post('/login/2fa',     [AdminAuthController::class, 'verify2fa'])->name('login.2fa.verify');
});

Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Dashboard & modules Super Admin — auth:admin obligatoire
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin', 'super.admin'])->group(function () {

    // Dashboard KPIs globaux
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Gestion des établissements
    // Route::prefix('etablissements')->name('etablissements.')->group(function () {
    //     Route::get('/',                          [EtablissementAdminController::class, 'index'])->name('index');
    //     Route::get('/{etablissement}',           [EtablissementAdminController::class, 'show'])->name('show');
    //     Route::patch('/{etablissement}/activer', [EtablissementAdminController::class, 'activer'])->name('activer');
    //     Route::patch('/{etablissement}/suspendre',[EtablissementAdminController::class, 'suspendre'])->name('suspendre');
    //     Route::delete('/{etablissement}',        [EtablissementAdminController::class, 'destroy'])->name('destroy');
    // });

    // Transactions globales
    //Route::get('transactions',    [TransactionAdminController::class, 'index'])->name('transactions.index');

    // Commissions
    // Route::prefix('commissions')->name('commissions.')->group(function () {
    //     Route::get('/',                                      [CommissionController::class, 'index'])->name('index');
    //     Route::get('/{etablissement}/modifier',              [CommissionController::class, 'edit'])->name('edit');
    //     Route::patch('/{etablissement}/modifier',            [CommissionController::class, 'update'])->name('update');
    // });

    // Logs de sécurité
    // Route::get('logs-securite',   [LogSecuriteController::class, 'index'])->name('logs.index');
    // Route::get('logs-securite/{log}', [LogSecuriteController::class, 'show'])->name('logs.show');

    // Paramètres système
    // Route::get('parametres',      [ParametreSystemeController::class, 'index'])->name('parametres.index');
    // Route::post('parametres',     [ParametreSystemeController::class, 'update'])->name('parametres.update');
});