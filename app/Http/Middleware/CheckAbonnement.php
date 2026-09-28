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

        // Le back-office establishment avait ce middleware côté web
        // (routes/web.php:140) mais PAS côté API : le groupe
        // `/api/v1/etablissement/*` laissait un établissement expiré
        // encaisser, saisir des paiements et annuler des opérations. Les
        // clients mobiles doivent répondre en JSON, jamais en redirect HTML.
        $estApi = $request->routeIs('api.*') || $request->expectsJson();
        if ($estApi) {
            $routesAutorisees = [
                'api.v1.etablissement.dashboard',
                'api.v1.etablissement.profil',
                'api.v1.etablissement.profil.*',
                'api.v1.etablissement.abonnement',
            ];
        }

        // Pas d'abonnement du tout
        if (!$abonnement) {
            if ($request->routeIs(...$routesAutorisees)) {
                return $next($request);
            }
            return $this->refuser($estApi, null, __('etablissement.abonnement_requis'));
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
            // Le groupe `api` n'a pas de StartSession : flashed sans garde,
            // cet avertissement aurait tente d'ouvrir une session sur une
            // requete JSON. L'API renvoie l'etat dans le corps du 402.
            if ($request->hasSession()) {
                session()->flash('warning_abonnement', __('etablissement.grace_alerte', [
                    'date'  => $abonnement->date_fin->format('d/m/Y'),
                    'grace' => $abonnement->grace_period_fin->format('d/m/Y'),
                ]));
            }
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
            return $this->refuser($estApi, $abonnement, __('etablissement.abonnement_expire', [
                'date' => $abonnement->date_fin?->format('d/m/Y'),
            ]));
        }

        return $next($request);
    }

    /**
     * 402 Payment Required : l'echec porte sur l'abonnement, pas sur les
     * droits de l'utilisateur. Le code `abonnement_requis` permet au mobile
     * d'afficher la bannière de renouvellement plutôt qu'une erreur générique.
     */
    private function refuser(bool $estApi, ?Abonnement $abonnement, string $message): Response
    {
        if (! $estApi) {
            return redirect()->route('etablissement.abonnement.requis');
        }

        return response()->json([
            'message'      => $message,
            'code'         => 'abonnement_requis',
            'etat'         => $abonnement?->etat(),
            'date_fin'     => $abonnement?->date_fin?->toDateString(),
            'grace_fin'    => $abonnement?->grace_period_fin?->toDateString(),
        ], 402);
    }
}
