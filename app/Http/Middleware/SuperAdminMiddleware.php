<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Vérifie que l'utilisateur connecté est bien un Super Admin EduPay.
     * Enregistre chaque accès dans les logs d'audit.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Admin|null $user */
        $user = Auth::guard('admin')->user();

        if (!$user) {
            return redirect()->route('admin.login')
                ->with('error', 'Accès non autorisé. Veuillez vous connecter.');
        }

        if (!$user->hasRole('super-admin')) {
            AuditLog::enregistrer(
                $user,
                'ACCES_REFUSE',
                'Tentative d\'accès Super Admin sans le rôle requis.',
                $request,
                'WARNING'
            );

            Auth::guard('admin')->logout();
            $request->session()->invalidate();

            abort(403, 'Accès interdit. Vous n\'avez pas les droits nécessaires.');
        }

        // Log chaque requête admin pour audit COBAC/BEAC
        AuditLog::enregistrer(
            $user,
            'ACCES_ADMIN',
            'Route : ' . $request->path(),
            $request,
            'INFO'
        );

        // Ajouter les headers de sécurité
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');

        return $response;
    }
}