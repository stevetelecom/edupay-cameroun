<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headers de securite appliques a TOUTES les reponses (M-02 audit).
 * Avant : X-Frame-Options / nosniff / Referrer-Policy n'etaient poses
 * que sur l'espace admin (SuperAdminMiddleware) — rien sur les vues
 * publiques, payeur ou etablissement, ni de CSP.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');

        // CSP : autorise les CDN deja utilises par les vues (Google Fonts,
        // Material Symbols) + auto-hebergement. A resserrer si de nouvelles
        // sources externes sont ajoutees plus tard.
        // En local (Vite dev server sur 5173), on autorise aussi le HMR websocket + le
        // chargement des scripts/styles depuis 127.0.0.1:5173.
        $viteDevServer = app()->environment('local')
            ? " http://127.0.0.1:5173 ws://127.0.0.1:5173"
            : "";

        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.datatables.net"
                . $viteDevServer . "; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.datatables.net"
                . $viteDevServer . "; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data: https:; "
            . "connect-src 'self'" . $viteDevServer . "; "
            . "frame-ancestors 'none';"
        );

        // HSTS : force HTTPS pendant 1 an, y compris sous-domaines.
        // Sans effet en HTTP local — actif uniquement derriere une vraie
        // connexion HTTPS (donc en production).
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
