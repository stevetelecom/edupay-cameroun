<?php

namespace App\Providers;

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
