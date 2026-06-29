<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $etablissementId = Auth::user()->etablissement_id;
        $anneeScolaire   = '2025-2026';
        $etablissement   = Auth::user()->etablissement;

        // Total encaissé ce mois (paiements validés, apprenants de l'établissement)
        $totalEncaisseMois = Paiement::where('statut', 'valide')
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant');

        // Total impayé (reste à payer sur les frais non réglés)
        $totalImpaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get()
            ->sum(fn ($f) => $f->montant_total - $f->montant_paye);

        // Nombre d'apprenants actifs
        $nbApprenants = \App\Models\Apprenant::where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->count();

        // Nombre de dossiers de frais impayés
        $nbDossiersImpayes = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->count();

        // Taux de recouvrement global
        $totalAttendu = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_total');

        $totalPaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_paye');

        $tauxRecouvrementDecimal = $totalAttendu > 0
            ? round(($totalPaye / $totalAttendu) * 100, 2)
            : 0;

        $tauxRecouvrement = (int) $tauxRecouvrementDecimal;

        // 5 derniers paiements reçus
        $derniersPaiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->latest('date_paiement')
            ->take(5)
            ->get();

        $countImpayes = $nbDossiersImpayes;

        return view('etablissement.dashboard', compact(
            'etablissement',
            'totalEncaisseMois',
            'totalImpaye',
            'nbApprenants',
            'nbDossiersImpayes',
            'derniersPaiements',
            'tauxRecouvrement',
            'tauxRecouvrementDecimal',
            'totalAttendu',
            'totalPaye',
            'countImpayes',
            'anneeScolaire',
        ));
    }
}
