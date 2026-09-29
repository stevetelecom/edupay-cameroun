<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;
use App\Support\AnneeScolaire;

class DashboardController extends Controller
{
    public function index()
    {
        $etablissementId = Auth::user()->etablissement_id;
        $etablissement   = Auth::user()->etablissement;
        $anneeScolaire   = AnneeScolaire::active($etablissement);

        // Total encaissé ce mois — filtré sur l'année scolaire ACTIVE via
        // frais_apprenant.annee_scolaire (pas juste le mois calendaire) pour ne
        // jamais mélanger avec des paiements liés à une année passée conservée.
        $totalEncaisseMois = Paiement::where('statut', 'valide')
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
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

        // Nombre de dossiers de frais ouverts sur l'année scolaire active
        // (0 => les indicateurs seront vides : le tableau de bord le signale)
        $nbFraisAnnee = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
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


        // Abonnement actif
        $abonnement = \App\Models\Abonnement::where('etablissement_id', $etablissementId)
            ->whereIn('statut', ['actif', 'grace_period'])
            ->latest()->first();

        // Répartition des paiements validés par moyen (année scolaire active)
        // — uniquement pour le graphique du tableau de bord, aucun impact métier.
        $repartitionMoyensAnnee = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
            ->selectRaw('mode_paiement, COUNT(*) as total, SUM(montant) as volume')
            ->groupBy('mode_paiement')
            ->get()
            ->keyBy('mode_paiement');

        // Histogramme des 14 derniers jours : encaissements journaliers validés.
        // Deux requêtes seulement : les montants du mois en cours, agrégés par
        // jour en PHP (les montants du mois précédent complètent la fenêtre).
        $montantsParJour = Paiement::where('statut', 'valide')
            ->where('date_paiement', '>=', now()->subDays(13)->startOfDay())
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->selectRaw('DATE(date_paiement) as jour, SUM(montant) as total')
            ->groupByRaw('DATE(date_paiement)')
            ->pluck('total', 'jour');

        $histogramme14Jours = collect(range(13, 0))->map(function ($i) use ($montantsParJour) {
            $date = now()->subDays($i);
            $cle  = $date->toDateString();
            return [
                'jour'   => $date->day,                    // numéro du jour affiché sous la barre
                'total'  => (int) ($montantsParJour[$cle] ?? 0),
                'actif'  => $date->isWeekday(),            // week-ends atténués
                'libelle'=> $date->locale(app()->getLocale())->isoFormat('ddd D MMM'),
            ];
        });

        return view('etablissement.dashboard', compact(
            'etablissement',
            'abonnement',
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
            'nbFraisAnnee',
            'repartitionMoyensAnnee',
            'histogramme14Jours',
        ));
    }
}
