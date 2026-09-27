<?php
namespace App\Http\Middleware;

use App\Models\Abonnement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAbonnement
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user || !$user->etablissement_id) {
            return $next($request);
        }

        $etablissement = $user->etablissement;
        if (!$etablissement) {
            return $next($request);
        }

        // L'établissement doit d'abord être validé (actif) par le Super Admin —
        // les statuts 'en_attente' et 'suspendu' sont déjà gérés dans le dashboard
        // lui-même (bannière dédiée). La question de l'abonnement ne se pose
        // qu'une fois l'établissement actif.
        if ($etablissement->statut !== 'actif') {
            return $next($request);
        }

        // Abonnement en cours = celui dont la période a commencé en dernier.
        //
        // On ne filtre plus sur `statut` : ce statut est une valeur DÉRIVÉE des
        // dates, recalculée ici et par la commande
        // abonnements:synchroniser. S'en servir pour trouver l'abonnement
        // courant créait un état impossible à sortir : une ligne repassée
        // 'expire' par une visite antérieure était alors considérée comme
        // « pas d'abonnement du tout », même après un renouvellement.
        //
        // Le tri sur date_debut rend aussi le choix déterministe : les
        // abonnements #6 et #7 de l'établissement 1 (doublons créés le même
        // jour) étaient départagés par created_at, donc au hasard.
        $abonnement = Abonnement::where('etablissement_id', $etablissement->id)
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->first();

        // Routes autorisées même sans abonnement valide : le tableau de bord
        // (seul écran encore accessible), la page d'abonnement (pour
        // renouveler), le profil et la déconnexion.
        $routesAutorisees = ['etablissement.profil.*', 'etablissement.abonnement.*', 'etablissement.dashboard', 'logout'];

        // Pas d'abonnement du tout
        if (!$abonnement) {
            if ($request->routeIs(...$routesAutorisees)) {
                return $next($request);
            }
            return redirect()->route('etablissement.abonnement.requis');
        }

        // L'état vient des DATES (Abonnement::etat()), plus du statut stocké :
        // c'est le seul moyen d'afficher « expiré » le jour même, sans
        // attendre qu'un utilisateur de l'établissement se connecte.
        $etat = $abonnement->etat();

        if ($etat === 'grace_period') {
            if ($abonnement->statutDesynchronise()) {
                $abonnement->update(['statut' => 'grace_period']);
            }

            // Alerte grace period. Le message etait en francais code en dur,
            // donc l'utilisateur anglais de l'application le lisait en francais.
            session()->flash('warning_abonnement', __('etablissement.grace_alerte', [
                'date'  => $abonnement->date_fin->format('d/m/Y'),
                'grace' => $abonnement->grace_period_fin->format('d/m/Y'),
            ]));
        }

        if ($etat === 'expire') {
            if ($abonnement->statutDesynchronise()) {
                $abonnement->update(['statut' => 'expire']);
            }

            // these two columns were NOT in Etablissement::$fillable, so this
            // update() was silently a no-op: the school kept displaying
            // "plan = standard" while being blocked.
            $etablissement->update([
                'plan_abonnement'      => 'aucun',
                'abonnement_expire_le' => $abonnement->date_fin?->toDateString(),
            ]);

            if ($request->routeIs(...$routesAutorisees)) {
                return $next($request);
            }
            return redirect()->route('etablissement.abonnement.requis');
        }

        return $next($request);
    }
}
