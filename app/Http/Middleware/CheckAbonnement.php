<?php
namespace App\Http\Middleware;

use App\Models\Abonnement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

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

        // Récupérer l'abonnement actif
        $abonnement = Abonnement::where('etablissement_id', $etablissement->id)
            ->whereIn('statut', ['actif', 'grace_period'])
            ->latest()
            ->first();

        // Pas d'abonnement du tout
        if (!$abonnement) {
            // Routes autorisées sans abonnement (profil, déconnexion)
            if ($request->routeIs('etablissement.profil.*', 'etablissement.abonnement.*', 'logout')) {
                return $next($request);
            }
            return redirect()->route('etablissement.abonnement.requis');
        }

        // Mettre à jour statut si en grace period
        if (Carbon::today()->gt($abonnement->date_fin) && Carbon::today()->lte($abonnement->grace_period_fin)) {
            $abonnement->update(['statut' => 'grace_period']);
            // Alerte grace period
            session()->flash('warning_abonnement',
                'Votre abonnement a expiré le ' . $abonnement->date_fin->format('d/m/Y') .
                '. Vous avez jusqu\'au ' . $abonnement->grace_period_fin->format('d/m/Y') .
                ' pour renouveler (grace period).');
        }

        // Grace period expirée → bloquer
        if (Carbon::today()->gt($abonnement->grace_period_fin)) {
            $abonnement->update(['statut' => 'expire']);
            $etablissement->update(['plan_abonnement' => 'aucun']);

            if ($request->routeIs('etablissement.profil.*', 'etablissement.abonnement.*', 'logout')) {
                return $next($request);
            }
            return redirect()->route('etablissement.abonnement.requis');
        }

        return $next($request);
    }
}
