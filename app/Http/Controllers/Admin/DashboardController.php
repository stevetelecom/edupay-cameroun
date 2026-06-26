<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\Reclamation;
use Carbon\Carbon;

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
        $reclamationsMois = Reclamation::whereBetween('created_at', [$debut, $fin])->count();

        $derniersEtablissements = Etablissement::orderByDesc('created_at')
            ->limit(5)
            ->get();

        $dernieresTransactions = Paiement::with(['apprenant', 'fraisApprenant'])
            ->where('statut', 'valide')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'volumeMois'                 => $volumeMois,
            'commissionsMois'            => $commissionsMois,
            'etablissementsActifs'       => $etablissementsActifs,
            'transactionsMois'           => $transactionsMois,
            'repartitionMoyens'          => $repartitionMoyens,
            'tauxRecouvrementGlobal'     => $tauxRecouvrementGlobal,
            'tauxParEtablissement'       => $tauxParEtablissement,
            'tauxParRegion'              => $tauxParRegion,
            'evolutionMensuelle'         => $evolutionMensuelle,
            'reclamationsMois'           => $reclamationsMois,
            'derniersEtablissements'     => $derniersEtablissements,
            'dernieresTransactions'      => $dernieresTransactions,
            'tauxCommission'             => 0.025,
            'pageTitle'                  => 'Tableau de bord — Super Admin EduPay',
            'mois'                       => now()->translatedFormat('F Y'),
        ]);
    }
}
