<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\Reclamation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard KPIs globaux — Super Admin.
     */
    public function index()
    {
        $debut = Carbon::now()->startOfMonth();
        $fin   = Carbon::now()->endOfMonth();

        // ────────────────────────────────────────────
        // 1. Métriques du mois
        // ────────────────────────────────────────────
        $volumeMois = Paiement::where('statut', 'valide')
            ->whereBetween('created_at', [$debut, $fin])
            ->sum('montant');

        // Volume du mois précédent : sert à la variation affichée sur la carte
        // KPI du dashboard (style maquette : « +12 % / hier »).
        $volumeMoisPrecedent = Paiement::where('statut', 'valide')
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])
            ->sum('montant');

        // Variation en % : null si pas de base de comparaison (on n'affiche rien
        // plutot qu'un « +∞ % » fallacieux).
        $variationVolume = $volumeMoisPrecedent > 0
            ? round((($volumeMois - $volumeMoisPrecedent) / $volumeMoisPrecedent) * 100, 1)
            : null;

        $transactionsMois = Paiement::where('statut', 'valide')
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        $commissionsMois = Commission::whereBetween('created_at', [$debut, $fin])
            ->sum('montant_commission');

        $repartitionMoyens = Paiement::where('statut', 'valide')
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw('mode_paiement, COUNT(*) as total, SUM(montant) as volume')
            ->groupBy('mode_paiement')
            ->get()
            ->keyBy('mode_paiement');

        // ────────────────────────────────────────────
        // 2. Taux de recouvrement GLOBAL (plateforme)
        // ────────────────────────────────────────────
        $montantTotalGlobal = FraisApprenant::sum('montant_total');
        $montantPayeGlobal  = FraisApprenant::sum('montant_paye');
        $tauxRecouvrementGlobal = $montantTotalGlobal > 0
            ? round(($montantPayeGlobal / $montantTotalGlobal) * 100, 2)
            : 0;

        // ────────────────────────────────────────────
        // 3. Taux de recouvrement PAR ÉTABLISSEMENT
        // ────────────────────────────────────────────
        $tauxParEtablissement = Etablissement::select('etablissements.id', 'etablissements.nom', 'etablissements.region', 'etablissements.ville')
            ->selectRaw('SUM(frais_apprenant.montant_total) as montant_total, SUM(frais_apprenant.montant_paye) as montant_paye')
            ->selectRaw('ROUND((SUM(frais_apprenant.montant_paye) / NULLIF(SUM(frais_apprenant.montant_total), 0)) * 100, 2) as taux_recouvrement')
            ->join('apprenants', 'etablissements.id', '=', 'apprenants.etablissement_id')
            ->join('frais_apprenant', 'apprenants.id', '=', 'frais_apprenant.apprenant_id')
            ->groupBy('etablissements.id', 'etablissements.nom', 'etablissements.region', 'etablissements.ville')
            ->orderByDesc('taux_recouvrement')
            ->limit(10)
            ->get();

        // ────────────────────────────────────────────
        // 4. Taux par région
        // ────────────────────────────────────────────
        $tauxParRegion = Etablissement::selectRaw('region, COUNT(*) as nb_etablissements, SUM(frais_apprenant.montant_total) as montant_total, SUM(frais_apprenant.montant_paye) as montant_paye')
            ->selectRaw('ROUND((SUM(frais_apprenant.montant_paye) / NULLIF(SUM(frais_apprenant.montant_total), 0)) * 100, 2) as taux_recouvrement')
            ->join('apprenants', 'etablissements.id', '=', 'apprenants.etablissement_id')
            ->join('frais_apprenant', 'apprenants.id', '=', 'frais_apprenant.apprenant_id')
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderByDesc('taux_recouvrement')
            ->get();

        // ────────────────────────────────────────────
        // 5. Évolution mensuelle (12 derniers mois)
        // ────────────────────────────────────────────
        $evolutionMensuelle = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = Carbon::now()->subMonths($i);
            $debut_m = $mois->copy()->startOfMonth();
            $fin_m   = $mois->copy()->endOfMonth();

            $montantTotal_m = FraisApprenant::whereBetween('created_at', [$debut_m, $fin_m])->sum('montant_total');
            $montantPaye_m  = FraisApprenant::whereBetween('created_at', [$debut_m, $fin_m])->sum('montant_paye');
            $taux_m = $montantTotal_m > 0 ? round(($montantPaye_m / $montantTotal_m) * 100, 2) : 0;

            $evolutionMensuelle[] = [
                'mois'  => $mois->translatedFormat('M Y'),
                'taux'  => $taux_m,
                'montant_paye' => $montantPaye_m,
                'montant_total' => $montantTotal_m,
            ];
        }

        // ────────────────────────────────────────────
        // Autres données
        // ────────────────────────────────────────────
        $etablissementsActifs = Etablissement::where('statut', 'actif')->count();
        $payeursTotaux = \App\Models\User::whereIn('profil', ['parent', 'eleve', 'etudiant'])->count();
        $reclamationsMois = Reclamation::whereBetween('created_at', [$debut, $fin])->count();

        $derniersEtablissements = Etablissement::orderByDesc('created_at')
            ->limit(5)
            ->get();

        $dernieresTransactions = Paiement::with(['apprenant', 'fraisApprenant'])
            ->where('statut', 'valide')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ────────────────────────────────────────────
        // 6. Couverture de la relation payeur ↔ apprenant (diagramme de Venn)
        //    Compte les comptes payeurs et les apprenants selon leur
        //    rattachement (table pivot user_apprenant) : seul un payeur
        //    rattaché peut recevoir des relances SMS/email.
        // ────────────────────────────────────────────
        $payeurIdsRattaches = DB::table('user_apprenant')->distinct()->pluck('user_id');
        $apprenantIdsRattaches = DB::table('user_apprenant')->distinct()->pluck('apprenant_id');
        $payeursRattaches = $payeurIdsRattaches->count();
        $apprenantsRattaches = $apprenantIdsRattaches->count();
        $payeursSeuls = \App\Models\User::whereIn('profil', ['parent', 'eleve', 'etudiant'])
            ->whereNotIn('id', $payeurIdsRattaches)->count();
        $apprenantsSeuls = Apprenant::whereNotIn('id', $apprenantIdsRattaches)->count();

        // ────────────────────────────────────────────
        // 7. Répartition des apprenants par statut de paiement
        //    (pictogrammes : 1 icône = 1 apprenant, cf. guide ultime)
        // ────────────────────────────────────────────
        $statutsApprenants = Apprenant::selectRaw('statut_paiement, COUNT(*) as total')
            ->groupBy('statut_paiement')
            ->pluck('total', 'statut_paiement');

        // ────────────────────────────────────────────
        // 8. Volume encaissé par jour de la semaine courante
        //    (histogramme hebdo, mois en cours)
        // ────────────────────────────────────────────
        $volumeParJour = collect([]);
        for ($j = 6; $j >= 0; $j--) {
            $jour = Carbon::now()->subDays($j);
            $volumeParJour->push([
                'jour' => $jour->translatedFormat('D'),
                'volume' => (int) Paiement::where('statut', 'valide')
                    ->whereDate('created_at', $jour->toDateString())
                    ->sum('montant'),
            ]);
        }

        return view('admin.dashboard', [
            'volumeMois'                 => $volumeMois,
            'variationVolume'            => $variationVolume,
            'commissionsMois'            => $commissionsMois,
            'etablissementsActifs'       => $etablissementsActifs,
            'payeursTotaux'              => $payeursTotaux,
            'transactionsMois'           => $transactionsMois,
            'repartitionMoyens'          => $repartitionMoyens,
            'tauxRecouvrementGlobal'     => $tauxRecouvrementGlobal,
            'tauxParEtablissement'       => $tauxParEtablissement,
            'tauxParRegion'              => $tauxParRegion,
            'evolutionMensuelle'         => $evolutionMensuelle,
            'reclamationsMois'           => $reclamationsMois,
            'derniersEtablissements'     => $derniersEtablissements,
            'dernieresTransactions'      => $dernieresTransactions,
            'payeursRattaches'           => $payeursRattaches,
            'apprenantsRattaches'        => $apprenantsRattaches,
            'payeursSeuls'               => $payeursSeuls,
            'apprenantsSeuls'            => $apprenantsSeuls,
            'statutsApprenants'          => $statutsApprenants,
            'volumeParJour'              => $volumeParJour,
            // tauxCommission + composantes (tauxAangaraaPct/margeEdupayPct) sont
            // fournis par AdminSidebarComposer : plus de valeur figée à 2,5 %.
            'pageTitle'                  => 'Tableau de bord — Super Admin EduPay',
            'mois'                       => now()->translatedFormat('F Y'),
        ]);
    }
}
