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

        // Relecture en base, et non `$user->est_actif` : l'instance fournie
        // par le guard est celle chargee au login (et, en test, celle passee
        // a actingAs()) — l'attribut peut yetre absent, et une valeur
        // devenue fausse en base serait lue comme vraie. C'est precisement le
        // cas qu'on veut attraper : un compte desactive en cours de session.
        $estActif = (bool) Admin::whereKey($user->id)->value('est_actif');

        if (! $estActif) {
            // Un compte desactive (suspension, depart) conserve sinon son
            // acces : `est_actif` n'etait ecrit que par le back office et
            // jamais relu. Avec le `remember: true` pose au login, le cookie
            // de session survivait a la suspension sans limite de duree.
            AuditLog::enregistrer(
                $user,
                'ACCES_REFUSE',
                'Compte desactive tente d\'atteindre l\'espace admin : ' . $request->path(),
                $request,
                'CRITICAL'
            );

            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'Ce compte a été désactivé. Contactez un Super Administrateur.');
        }

        // Routes reservees au super-admin uniquement.
        //
        // Cette liste est le SEUL controle de role de l'espace admin : aucun
        // controleur ne verifie le role lui-meme. Elle ne couvrait que 4
        // motifs sur 54 routes, alors que `superviseur` est/libelle « Lecture
        // + rapports » (resources/lang/fr/admin.php:268) et pouvait donc
        // prelever de vraies commissions, detruire des abonnements, suspendre
        // des etablissements ou supprimer des payeurs.
        $routesSupAdminSeulement = [
            // Comptes administrateurs et parametres systeme.
            'admin.admins.*',
            'admin.parametres.*',

            // Abonnements : creation, renouvellement et suppression changent
            // ce que l'etablissement est habilite a faire et pour combien de
            // mois. Un superviseur « lecture + rapports » ne les touche pas.
            'admin.abonnements.store',
            'admin.abonnements.update',
            'admin.abonnements.renouveler',
            'admin.abonnements.destroy',

            // Argent sortant : prelevement de commission et rejeu.
            'admin.commissions.prelever',
            'admin.commissions.rejouer',
            'admin.commissions.update',

            // Suppression d'etablissement.
            'admin.etablissements.destroy',
            'admin.etablissements.bulkDestroy',

            // Cycle de vie d'un etablissement (suspendre revoque tous les acces).
            'admin.etablissements.activer',
            'admin.etablissements.suspendre',

            // Suppression massive de donnees payeur.
            'admin.payeurs.destroy',
            'admin.payeurs.bulkDestroy',
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

        // `comptable_plateforme` est decrit comme « Commissions + exports ».
        // Lire les commissions est permis, mais en modifier le taux releve de
        // la gestion, pas du reporting.
        if (! $user->hasRole('super-admin')
            && $user->hasRole('comptable_plateforme')
            && $request->routeIs('admin.commissions.edit')) {
            abort(403, 'Le taux de commission est réservé au Super Administrateur.');
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
