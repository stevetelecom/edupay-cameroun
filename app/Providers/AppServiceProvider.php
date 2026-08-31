<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Partage les indicateurs globaux (taux de recouvrement + nb impayés)
        // à la sidebar du back-office établissement -> identiques sur TOUS les onglets.
        View::composer(
            'layouts.etablissement',
            \App\View\Composers\EtablissementSidebarComposer::class
        );

        // Le taux de recouvrement est aussi affiché dans le contenu de la page
        // impayés -> même valeur partagée.
        View::composer(
            'etablissement.impayes.index',
            \App\View\Composers\EtablissementSidebarComposer::class
        );

        // Propage automatiquement le montant d'une catégorie de frais modifiée
        // aux dossiers frais (FraisApprenant) où elle est affectée (sans paiement).
        \App\Models\CategoriesFrais::observe(\App\Observers\CategoriesFraisObserver::class);

        // Redirige un utilisateur déjà connecté qui tente d'accéder à une
        // page 'guest' vers SON tableau de bord réel (pas '/' par défaut,
        // qui ne connaît que les routes nommées 'dashboard' ou 'home').
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            if (\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
                return route('admin.dashboard');
            }
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user->hasAnyRole(['directeur', 'comptable', 'caissier'])) {
                    return route('etablissement.dashboard');
                }
                return route('payeur.dashboard');
            }
            return route('landing');
        });

        //
    }
}
