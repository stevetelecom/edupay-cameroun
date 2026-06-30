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
     * Vérifie que l'utilisateur connecté a un rôle admin autorisé.
     * Super-admin : accès total.
     * Superviseur / Comptable_plateforme : accès limité (lecture + rapports).
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Admin|null $user */
        $user = Auth::guard('admin')->user();

        if (!$user) {
            return redirect()->route('admin.login')
                ->with('error', 'Accès non autorisé. Veuillez vous connecter.');
        }

        // Rôles autorisés à accéder à l'espace admin
        $rolesAutorises = ['super-admin', 'superviseur', 'comptable_plateforme'];

        if (!$user->hasAnyRole($rolesAutorises)) {
            AuditLog::enregistrer(
                $user,
                'ACCES_REFUSE',
                'Tentative d\'accès espace admin sans rôle autorisé.',
                $request,
                'WARNING'
            );
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            abort(403, 'Accès interdit. Vous n\'avez pas les droits nécessaires.');
        }

        // Routes réservées au super-admin uniquement
        $routesSupAdminSeulement = [
            'admin.admins.*',
            'admin.parametres.*',
            'admin.etablissements.destroy',
        ];

        foreach ($routesSupAdminSeulement as $pattern) {
            if ($request->routeIs($pattern) && !$user->hasRole('super-admin')) {
                AuditLog::enregistrer(
                    $user,
                    'ACCES_REFUSE',
                    'Tentative d\'accès route super-admin : ' . $request->path(),
                    $request,
                    'WARNING'
                );
                abort(403, 'Cette section est réservée au Super Administrateur.');
            }
        }

        // Log chaque requête admin pour audit COBAC/BEAC
        AuditLog::enregistrer(
            $user,
            'ACCES_ADMIN',
            'Route : ' . $request->path(),
            $request,
            'INFO'
        );

        // Headers de sécurité
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        return $response;
    }
}
