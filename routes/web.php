<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterParentController;
use App\Http\Controllers\Auth\RegisterEcolController;
use App\Http\Controllers\Public\LandingController;
use Illuminate\Support\Facades\Auth;
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

/*
|--------------------------------------------------------------------------
| Webhook AangaraaPay — public, sans CSRF
|--------------------------------------------------------------------------
*/
Route::post('/webhook/aangaraapay',
    [\App\Http\Controllers\Payeur\PaiementController::class, 'webhook']
)->name('payeur.paiement.webhook');

Route::middleware(['auth', 'role:parent|eleve'])->prefix('espace')->name('payeur.')->group(function () {
    Route::get('/onboarding', [\App\Http\Controllers\Payeur\OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding', [\App\Http\Controllers\Payeur\OnboardingController::class, 'store'])->name('onboarding.store');
    Route::get('/tableau-de-bord', [\App\Http\Controllers\Payeur\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/paiement/{fraisApprenant}', [\App\Http\Controllers\Payeur\PaiementController::class, 'show'])->name('paiement.show');
    Route::post('/paiement/{fraisApprenant}/initier', [\App\Http\Controllers\Payeur\PaiementController::class, 'initier'])->name('paiement.initier');
    Route::get('/paiement/{paiement}/attente',  [\App\Http\Controllers\Payeur\PaiementController::class, 'attente'])->name('paiement.attente');
    Route::get('/paiement/{paiement}/statut',   [\App\Http\Controllers\Payeur\PaiementController::class, 'verifierStatut'])->name('paiement.statut');
    Route::get('/historique', [\App\Http\Controllers\Payeur\PaiementController::class, 'historique'])->name('historique');
    Route::get('/recus', [\App\Http\Controllers\Payeur\RecuController::class, 'index'])->name('recus.index');
    Route::get('/recus/{paiement}/telecharger', [\App\Http\Controllers\Payeur\RecuController::class, 'telechargerRecu'])->name('recus.telecharger');
    Route::get('/certificat/{apprenant}', [\App\Http\Controllers\Payeur\RecuController::class, 'genererCertificat'])->name('recus.certificat');
    Route::get('/reclamations', [\App\Http\Controllers\Payeur\ReclamationController::class, 'index'])->name('reclamations.index');
    Route::post('/reclamations', [\App\Http\Controllers\Payeur\ReclamationController::class, 'store'])->name('reclamations.store');
    Route::get('/profil', [\App\Http\Controllers\Payeur\ProfilController::class, 'index'])->name('profil.index');
    Route::put('/profil/infos', [\App\Http\Controllers\Payeur\ProfilController::class, 'updateInfos'])->name('profil.infos');
    Route::put('/profil/notifications', [\App\Http\Controllers\Payeur\ProfilController::class, 'updateNotifications'])->name('profil.notifications');
    Route::put('/profil/password', [\App\Http\Controllers\Payeur\ProfilController::class, 'updatePassword'])->name('profil.password');

    // F04 — Modifier/détacher un rattachement apprenant
    Route::get('/mes-apprenants/{apprenant}/modifier', [\App\Http\Controllers\Payeur\OnboardingController::class, 'editApprenant'])->name('apprenant.edit');
    Route::put('/mes-apprenants/{apprenant}/modifier', [\App\Http\Controllers\Payeur\OnboardingController::class, 'updateApprenant'])->name('apprenant.update');
    Route::delete('/mes-apprenants/{apprenant}',       [\App\Http\Controllers\Payeur\OnboardingController::class, 'detachApprenant'])->name('apprenant.detach');

    // F05 — Vue frais détaillés par apprenant
    Route::get('/mes-apprenants/{apprenant}/frais',    [\App\Http\Controllers\Payeur\PaiementController::class, 'fraisApprenant'])->name('frais.apprenant');
});



// Route::get('/debug-auth', function () {
//     return [
//         'check' => Auth::check(),
//         'user_id' => Auth::id(),
//         'user_name' => Auth::check() ? Auth::user()->name : null,
//         'roles' => Auth::check() ? Auth::user()->getRoleNames() : null,
//         'guard_default' => config('auth.defaults.guard'),
//     ];
// });
/*
|--------------------------------------------------------------------------
| Routes Établissement
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:directeur|comptable|caissier'])->prefix('etablissement')->name('etablissement.')->group(function () {
    Route::get('/tableau-de-bord', [\App\Http\Controllers\Etablissement\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('apprenants', \App\Http\Controllers\Etablissement\ApprenantController::class);

    // Frais & Échéanciers (E02 / E03)
    Route::get('/frais',                    [\App\Http\Controllers\Etablissement\FraisController::class, 'index'])->name('frais.index');
    Route::get('/frais/create',             [\App\Http\Controllers\Etablissement\FraisController::class, 'create'])->name('frais.create');
    Route::post('/frais',                   [\App\Http\Controllers\Etablissement\FraisController::class, 'store'])->name('frais.store');
    Route::get('/frais/{frais}/edit',   [\App\Http\Controllers\Etablissement\FraisController::class, 'edit'])->name('frais.edit');
    Route::put('/frais/{frais}',        [\App\Http\Controllers\Etablissement\FraisController::class, 'update'])->name('frais.update');
    Route::delete('/frais/{frais}',     [\App\Http\Controllers\Etablissement\FraisController::class, 'destroy'])->name('frais.destroy');

    Route::get('/apprenants/import/template', [\App\Http\Controllers\Etablissement\ApprenantController::class, 'importTemplate'])
         ->name('apprenants.import.template');
    Route::post('/apprenants/import', [\App\Http\Controllers\Etablissement\ApprenantController::class, 'import'])
         ->name('apprenants.import');
    Route::get('/paiements',  [\App\Http\Controllers\Etablissement\PaiementController::class, 'index'])->name('paiements.index');
    Route::get('/impayes',    [\App\Http\Controllers\Etablissement\ImpayeController::class, 'index'])->name('impayes.index');
    Route::post('/impayes/relancer', [\App\Http\Controllers\Etablissement\ImpayeController::class, 'relancerSms'])->name('impayes.relancer');
    Route::post('/impayes/{apprenant}/relancer', [\App\Http\Controllers\Etablissement\ImpayeController::class, 'relancerApprenant'])->name('impayes.relancer.apprenant');
    Route::get('/rapports',   [\App\Http\Controllers\Etablissement\RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/export/pdf', [\App\Http\Controllers\Etablissement\RapportController::class, 'exportPdf'])->name('rapports.export.pdf');
    Route::get('/rapports/export/excel', [\App\Http\Controllers\Etablissement\RapportController::class, 'exportExcel'])->name('rapports.export.excel');
    Route::get('/parametres', [\App\Http\Controllers\Etablissement\ParametreController::class, 'index'])->name('parametres.index');
    Route::put('/parametres', [\App\Http\Controllers\Etablissement\ParametreController::class, 'update'])->name('parametres.update');
    Route::get('/utilisateurs', [\App\Http\Controllers\Etablissement\UtilisateurController::class, 'index'])->name('utilisateurs.index');
    Route::post('/utilisateurs', [\App\Http\Controllers\Etablissement\UtilisateurController::class, 'store'])->name('utilisateurs.store');
    Route::put('/utilisateurs/{utilisateur}/role', [\App\Http\Controllers\Etablissement\UtilisateurController::class, 'updateRole'])->name('utilisateurs.role');
    Route::delete('/utilisateurs/{utilisateur}', [\App\Http\Controllers\Etablissement\UtilisateurController::class, 'destroy'])->name('utilisateurs.destroy');
    Route::get('/remboursements', [\App\Http\Controllers\Etablissement\RemboursementController::class, 'index'])->name('remboursements.index');
    Route::post('/remboursements', [\App\Http\Controllers\Etablissement\RemboursementController::class, 'store'])->name('remboursements.store');
    Route::post('/remboursements/{remboursement}/approuver', [\App\Http\Controllers\Etablissement\RemboursementController::class, 'approuver'])->name('remboursements.approuver');
    Route::post('/remboursements/{remboursement}/refuser', [\App\Http\Controllers\Etablissement\RemboursementController::class, 'refuser'])->name('remboursements.refuser');
    Route::get('/sites', [\App\Http\Controllers\Etablissement\SiteController::class, 'index'])->name('sites.index');
    Route::post('/sites', [\App\Http\Controllers\Etablissement\SiteController::class, 'store'])->name('sites.store');
});


/*
|--------------------------------------------------------------------------
| Routes Super Admin — URL cachée
|--------------------------------------------------------------------------
*/
Route::prefix(env('ADMIN_URL_PREFIX', 'admin-ep2026'))
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

