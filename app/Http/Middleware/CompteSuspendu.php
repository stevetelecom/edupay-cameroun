<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compte suspendu : plus aucune requete acceptee, et les sessions vivantes
 * sont revoquees.
 *
 * S (revocation Sanctum) : la suspension d'un compte n'etait verifiee
 * qu'a la CONNEXION. Un token deja emis (telephone vole, session laissee
 * ouverte) restait valable indefiniment apres la suspension, et apres un
 * changement de mot de passe. Ce middleware ferme la porte a chaque
 * requete et supprime les tokens au passage.
 *
 * Volontairement limite au suspension du COMPTE (`users.suspendu`) :
 * l'etablissement lui peut etre suspendu par le Super Admin tout en
 * laissant ses equipes consulter le back-office (bandeau dedie dans le
 * dashboard), comportement conserve.
 */
class CompteSuspendu
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->suspendu) {
            return $next($request);
        }

        // Suspension : on coupe immediatement toutes les sessions du compte.
        // Le token qui porte la requete courante est invalide des la
        // reponse ; les autres restent inutilisables a la requete suivante.
        $user->tokens()->delete();

        return response()->json([
            'message' => $user->suspendu_raison
                ? __('api.compte_suspendu_raison', ['raison' => $user->suspendu_raison])
                : __('api.compte_suspendu'),
            'code'    => 'compte_suspendu',
        ], 403);
    }
}
