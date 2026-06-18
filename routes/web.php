<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterParentController;
use App\Http\Controllers\Auth\RegisterEcolController;
use App\Http\Controllers\Public\LandingController;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/a-propos', [LandingController::class, 'about'])->name('about');
Route::get('/temoignages', [LandingController::class, 'temoignages'])->name('temoignages');

// Authentification
Route::middleware('guest')->group(function () {
    Route::get('/connexion',          [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/connexion',         [LoginController::class, 'login'])->name('login.post');
    Route::get('/connexion/otp',      [LoginController::class, 'showOtpForm'])->name('login.otp');
    Route::post('/connexion/otp',     [LoginController::class, 'verifyOtp'])->name('login.otp.verify');

    // Inscription Parent (3 étapes)
    Route::get('/inscription/parent',            [RegisterParentController::class, 'step1'])->name('register.parent.step1');
    Route::post('/inscription/parent/step1',     [RegisterParentController::class, 'storeStep1'])->name('register.parent.step1.post');
    Route::get('/inscription/parent/step2',      [RegisterParentController::class, 'step2'])->name('register.parent.step2');
    Route::post('/inscription/parent/step2',     [RegisterParentController::class, 'storeStep2'])->name('register.parent.step2.post');
    Route::get('/inscription/parent/confirmer',  [RegisterParentController::class, 'confirm'])->name('register.parent.confirm');
    Route::post('/inscription/parent/confirmer', [RegisterParentController::class, 'store'])->name('register.parent.store');

    // Inscription Établissement (4 étapes)
    Route::get('/inscription/etablissement',             [RegisterEcolController::class, 'step1'])->name('register.ecole.step1');
    Route::post('/inscription/etablissement/step1',      [RegisterEcolController::class, 'storeStep1'])->name('register.ecole.step1.post');
    Route::get('/inscription/etablissement/step2',       [RegisterEcolController::class, 'step2'])->name('register.ecole.step2');
    Route::post('/inscription/etablissement/step2',      [RegisterEcolController::class, 'storeStep2'])->name('register.ecole.step2.post');
    Route::get('/inscription/etablissement/step3',       [RegisterEcolController::class, 'step3'])->name('register.ecole.step3');
    Route::post('/inscription/etablissement/step3',      [RegisterEcolController::class, 'storeStep3'])->name('register.ecole.step3.post');
    Route::get('/inscription/etablissement/validation',  [RegisterEcolController::class, 'validation'])->name('register.ecole.validation');
    Route::post('/inscription/etablissement/validation', [RegisterEcolController::class, 'store'])->name('register.ecole.store');
});

// Déconnexion
Route::post('/deconnexion', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Routes Payeur (Parent / Étudiant)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:payeur'])->prefix('espace')->name('payeur.')->group(function () {
    Route::get('/tableau-de-bord', [\App\Http\Controllers\Payeur\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/paiement/{fraisApprenant}', [\App\Http\Controllers\Payeur\PaiementController::class, 'show'])->name('paiement.show');
    Route::post('/paiement/{fraisApprenant}/initier', [\App\Http\Controllers\Payeur\PaiementController::class, 'initier'])->name('paiement.initier');
    Route::get('/historique', [\App\Http\Controllers\Payeur\PaiementController::class, 'historique'])->name('historique');
});

/*
|--------------------------------------------------------------------------
| Routes Établissement
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:directeur|comptable|caissier'])->prefix('etablissement')->name('etablissement.')->group(function () {
    Route::get('/tableau-de-bord', [\App\Http\Controllers\Etablissement\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('apprenants', \App\Http\Controllers\Etablissement\ApprenantController::class);
    Route::get('/paiements',  [\App\Http\Controllers\Etablissement\PaiementController::class, 'index'])->name('paiements.index');
    Route::get('/impayes',    [\App\Http\Controllers\Etablissement\ImpayeController::class, 'index'])->name('impayes.index');
    Route::post('/impayes/relancer', [\App\Http\Controllers\Etablissement\ImpayeController::class, 'relancerSms'])->name('impayes.relancer');
    Route::get('/rapports',   [\App\Http\Controllers\Etablissement\RapportController::class, 'index'])->name('rapports.index');
    Route::get('/parametres', [\App\Http\Controllers\Etablissement\ParametreController::class, 'index'])->name('parametres.index');
});

/*
|--------------------------------------------------------------------------
| Routes Super Admin — URL cachée
|--------------------------------------------------------------------------
*/
Route::prefix(env('ADMIN_URL_PREFIX', 'admin-ep2026'))
    ->name('admin.')
    ->group(base_path('routes/admin.php'));
