<?php

namespace App\Http\Middleware;

use App\Models\ParametreSysteme;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Bloque l'acces a la plateforme si le mode maintenance est actif (S07 CDC).
     * Exceptions : Super Admin connecte, espace Super Admin (login/2fa inclus),
     * et le webhook AangaraaPay (les paiements en cours ne doivent jamais etre coupes).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maintenanceActif = ParametreSysteme::obtenirBool('maintenance', false);

        if (! $maintenanceActif) {
            return $next($request);
        }

        // Toujours laisser passer l'espace Super Admin (pour pouvoir desactiver le mode)
        if ($request->routeIs('admin.*')) {
            return $next($request);
        }

        // Toujours laisser passer le webhook de paiement (transactions en cours)
        if ($request->is('webhook/aangaraapay')) {
            return $next($request);
        }

        // Un admin deja connecte peut naviguer partout meme en maintenance
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}
