<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;
        $anneeScolaire   = '2025-2026';

        $totalEncaisseAnnee = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant');

        $totalImpayeAnnee = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get()
            ->sum(fn ($f) => $f->montant_total - $f->montant_paye);

        $totalAttendu = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_total');

        $tauxRecouvrement = $totalAttendu > 0
            ? round((($totalAttendu - $totalImpayeAnnee) / $totalAttendu) * 100)
            : 0;

        $nbApprenants = Apprenant::where('etablissement_id', $etablissementId)->count();

        // Répartition par moyen de paiement (sur paiements validés)
        $totalValideTous = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant');

        $repartitionMoyens = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->selectRaw('mode_paiement, SUM(montant) as total')
            ->groupBy('mode_paiement')
            ->get()
            ->map(function ($row) use ($totalValideTous) {
                return [
                    'mode'        => $row->mode_paiement,
                    'pourcentage' => $totalValideTous > 0 ? round(($row->total / $totalValideTous) * 100) : 0,
                ];
            })
            ->toArray();

        // Recouvrement par classe
        $repartitionClasses = Apprenant::where('etablissement_id', $etablissementId)
            ->selectRaw('classe, COUNT(*) as nb_apprenants')
            ->groupBy('classe')
            ->orderBy('classe')
            ->get()
            ->map(function ($row) use ($anneeScolaire) {
                $frais = FraisApprenant::where('annee_scolaire', $anneeScolaire)
                    ->whereHas('apprenant', fn ($q) => $q->where('classe', $row->classe))
                    ->get();

                $attendu = $frais->sum('montant_total');
                $paye    = $frais->sum('montant_paye');

                return [
                    'nom'           => $row->classe,
                    'nb_apprenants' => $row->nb_apprenants,
                    'taux'          => $attendu > 0 ? round(($paye / $attendu) * 100) : 0,
                ];
            })
            ->toArray();

        return view('etablissement.rapports.index', compact(
            'totalEncaisseAnnee',
            'totalImpayeAnnee',
            'tauxRecouvrement',
            'nbApprenants',
            'repartitionMoyens',
            'repartitionClasses',
            'anneeScolaire',
        ));
    }
}
