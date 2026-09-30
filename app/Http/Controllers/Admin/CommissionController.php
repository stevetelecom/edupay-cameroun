<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Jobs\ReverserEtablissementJob;
use App\Models\Etablissement;
use App\Models\AuditLog;
use App\Models\ParametreSysteme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Commission::with([
                'etablissement',
                'paiement',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('etablissement_id')) {
            $query->where('etablissement_id', $request->etablissement_id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('etablissement', function($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%");
            });
        }

        $commissions = $query->paginate(20)->withQueryString();

        // whereMonth seul ne filtre que le MOIS, pas l'annee : le total
        // affichait aussi les commissions du meme mois des annees precedentes
        // (en septembre 2026, celles de septembre 2025 etaient comptees).
        $debutMois = now()->startOfMonth();
        $finMois   = now()->endOfMonth();

        $stats = [
            'total_mois'   => Commission::whereBetween('created_at', [$debutMois, $finMois])
                                    ->sum('montant_commission'),
            'nb_mois'      => Commission::whereBetween('created_at', [$debutMois, $finMois])->count(),
            'calculees'    => Commission::where('statut', Commission::STATUT_CALCULEE)->count(),
            'prelevees'    => Commission::where('statut', Commission::STATUT_PRELEVEE)->count(),

            // Argent bloque jamais parti vers l'etablissement. Chiffre a suivre en
            // priorite : avant ces stats, un reversement bloque etait invisible.
            'a_traiter'    => Commission::whereIn('statut', Commission::STATUTS_A_TRAITER)->count(),
            'montant_bloque' => Commission::whereIn('statut', Commission::STATUTS_A_TRAITER)
                                    ->sum('montant_net_etablissement'),
        ];

        $etablissements = Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom']);

        // Taux reellement applique aux paiements, lu dans les parametres
        // systeme. Lire la config ('taux_commission' fige a 2,5 % dans
        // .env) affichait un taux qui ne correspondait plus aux frais
        // preleves, et le taux propre a l'etablissement n'entre dans aucun
        // calcul : il est conserve comme libelle sur la commission.
        $serviceFrais  = app(\App\Services\AangaraaPayService::class);
        $tauxActuel    = $serviceFrais->tauxFraisService();
        $tauxAangaraa  = $serviceFrais->tauxAangaraa();
        $margeEdupay   = $serviceFrais->margeEdupay();

        // Taux reellement preleves, par profil d'abonnement (CDC S0 #3).
        $tauxParPlan   = $serviceFrais->tauxParPlan();

        return view('admin.commissions.index', compact(
            'commissions', 'stats', 'etablissements', 'tauxActuel',
            'tauxAangaraa', 'margeEdupay', 'tauxParPlan'
        ));
    }

    public function edit(Etablissement $etablissement)
    {
        $serviceFrais = app(\App\Services\AangaraaPayService::class);

        return view('admin.commissions.edit', [
            'etablissement' => $etablissement,
            'tauxActuel'    => $serviceFrais->tauxFraisService(),
            'tauxAangaraa'  => $serviceFrais->tauxAangaraa(),
            'margeEdupay'   => $serviceFrais->margeEdupay(),
        ]);
    }

    /**
     * Taux global depuis la page Commissions.
     *
     * Le bandeau de l'index affichait un bouton « configurer le taux global »
     * qui pointait sur /commissions/global/modifier. Cette URI tombait sur le
     * meme segment que {etablissement} : la resolution de modelo cherchait un
     * etablissement appele "global", introuvable, 404. Le taux global est en
     * realite taux_aangaraa + marge_edupay dans parametres_systeme, d'ou cette
     * route dediee qui ecrit au bon endroit.
     */
    public function updateTauxGlobal(Request $request)
    {
        $serviceFrais = app(\App\Services\AangaraaPayService::class);
        $tauxAangaraa = $serviceFrais->tauxAangaraa();

        $request->validate([
            'taux_global' => ['required', 'numeric', 'min:0.001', 'max:1'],
        ], [
            'taux_global.required' => 'Le taux global est obligatoire.',
            'taux_global.min'      => 'Le taux global minimum est 0,1%.',
            'taux_global.max'      => 'Le taux global maximum est 100%.',
        ]);

        $tauxAangaraaAvant = $tauxAangaraa;
        $margeEdupayAvant  = $serviceFrais->margeEdupay();
        $tauxGlobal = (float) $request->taux_global;

        // La part AangaraaPay couvre le cout reel du reversement : l'abaisser
        // sous le cout du prestataire ferait perdre de l'argent a chaque
        // paiement. On garde la repartition actuelle et on ajuste la marge
        // EduPay, qui est la seule part réellement pilotable ici.
        $margeEdupay = round($tauxGlobal - $tauxAangaraaAvant, 6);

        if ($margeEdupay < 0) {
            return back()->withErrors([
                'taux_global' => sprintf(
                    'Le taux global ne peut pas etre inferieur au cout AangaraaPay (%s%%). '
                    .'Augmentez d\'abord ce cout dans les parametres systeme.',
                    number_format($tauxAangaraaAvant * 100, 2)
                ),
            ])->withInput();
        }

        // Le taux global reste le taux de REPLI (etablissement sans
        // abonnement). Les taux par plan sont ajustes a l'identique : sinon
        // changer le taux global n'aurait aucun effet sur une institution abonnee,
        // ce qui rendrait le reglage trompeur.
        $ajustes = [];
        foreach (array_keys(\App\Services\AangaraaPayService::TAUX_COMMISSION_PLANS) as $plan) {
            $ajustes['taux_commission_'.$plan] = $tauxGlobal;
        }

        ParametreSysteme::definir(array_merge(['marge_edupay' => $margeEdupay], $ajustes));

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'TAUX_GLOBAL_MODIFIE',
            sprintf(
                'Taux global : %s%% -> %s%% (marge EduPay %s%% -> %s%%, taux AangaraaPay inchange a %s%%, taux des 3 plans alignes)',
                number_format($tauxAangaraaAvant + $margeEdupayAvant, 2),
                number_format($tauxGlobal, 2),
                number_format($margeEdupayAvant, 2),
                number_format($margeEdupay, 2),
                number_format($tauxAangaraaAvant, 2)
            ),
            $request, 'WARNING',
            ['marge_edupay' => $margeEdupayAvant],
            ['marge_edupay' => $margeEdupay]
        );

        return back()->with('success', 'Taux global mis a jour.');
    }

    /**
     * Taux de commission par profil d'abonnement (CDC S0 #3).
     *
     * Le taux preleve depend du plan souscrit par l'etablissement, pas d'un
     * reglage libre par etablissement : c'est ce que demande le cahier des
     * charges. Chaque plan se regle independamment, avec un plancher impose :
     * le taux ne peut pas descendre sous le cout AangaraaPay (2,2 %), sinon la
     * plateforme perd de l'argent sur chaque reversement.
     */
    public function updateTauxParPlan(Request $request)
    {
        $serviceFrais = app(\App\Services\AangaraaPayService::class);
        $tauxAangaraa = $serviceFrais->tauxAangaraa();
        $plans = array_keys(\App\Services\AangaraaPayService::TAUX_COMMISSION_PLANS);

        $messages = [];
        foreach ($plans as $plan) {
            $messages['taux_'.$plan.'.required'] = sprintf('Le taux du plan %s est obligatoire.', $plan);
            $messages['taux_'.$plan.'.min']      = sprintf('Le taux du plan %s ne peut pas etre inferieur au cout AangaraaPay (%.2f%%).', $plan, $tauxAangaraa * 100);
            $messages['taux_'.$plan.'.max']      = sprintf('Le taux du plan %s ne peut pas depasser 100%%.', $plan);
        }

        $regles = [];
        foreach ($plans as $plan) {
            $regles['taux_'.$plan] = ['required', 'numeric', 'min:'.$tauxAangaraa, 'max:1'];
        }

        $request->validate($regles, $messages);

        $avant = $serviceFrais->tauxParPlan();
        $nouvelles = [];

        foreach ($plans as $plan) {
            $nouvelles['taux_commission_'.$plan] = (float) $request->input('taux_'.$plan);
        }

        ParametreSysteme::definir($nouvelles);

        $lignes = [];
        foreach ($plans as $plan) {
            $lignes[] = sprintf(
                '%s %s%% -> %s%%',
                $plan,
                number_format($avant[$plan] * 100, 2),
                number_format($nouvelles['taux_commission_'.$plan] * 100, 2)
            );
        }

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'TAUX_COMMISSION_PLANS_MODIFIES',
            'Taux de commission par profil d\'abonnement : '.implode(', ', $lignes),
            $request, 'WARNING',
            $avant,
            $serviceFrais->tauxParPlan()
        );

        return back()->with('success', 'Taux de commission par profil d\'abonnement mis a jour.');
    }

    public function marquerPrelevee(Request $request, Commission $commission)
    {
        if ($commission->statut === Commission::STATUT_PRELEVEE) {
            return back()->with('error', 'Cette commission est deja marquee comme prelevee.');
        }

        $commission->update([
            'statut'      => Commission::STATUT_PRELEVEE,
            'reversed_at' => now(),
        ]);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'COMMISSION_PRELEVEE',
            "Commission #{$commission->id} ({$commission->statut} -> prelevee) validee manuellement",
            $request, 'WARNING',
            ['statut' => $commission->statut],
            ['statut' => Commission::STATUT_PRELEVEE]
        );

        return back()->with('success', 'Commission marquee comme prelevee.');
    }

    /**
     * Relance un reversement bloque depuis l'interface admin.
     *
     * Volontairement refuse pour l'etat « a verifier » : cet etat signifie
     * que l'argent est peut-etre deja parti. Le seul moyen de forcer reste la
     * commande artisan --inclure-a-verifier, tracee et explicite.
     */
    public function rejouerReversement(Request $request, Commission $commission)
    {
        if ($commission->statut === Commission::STATUT_A_VERIFIER) {
            return back()->with(
                'error',
                "Commission #{$commission->id} en attente de verification : l'argent est peut-etre deja parti. "
                .'Verifiez d\'abord la reference dans l\'historique AangaraaPay pour eviter un double paiement.'
            );
        }

        if ($commission->statut === Commission::STATUT_PRELEVEE) {
            return back()->with('error', 'Cette commission a deja ete reversee.');
        }

        ReverserEtablissementJob::dispatch($commission->id);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'COMMISSION_REVERSEMENT_REJOUE',
            "Relance manuelle du reversement de la commission #{$commission->id} (etat {$commission->statut})",
            $request, 'WARNING',
            ['statut' => $commission->statut]
        );

        return back()->with('success', "Relance du reversement #{$commission->id} mise en file.");
    }
}
