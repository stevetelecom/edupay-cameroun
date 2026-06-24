<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->genererDonneesRapport();

        return view('etablissement.rapports.index', $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->genererDonneesRapport();

        $pdf = Pdf::loadView('pdf.rapport', $data);

        return $pdf->download('rapport-financier-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $data = $this->genererDonneesRapport();
        $nomFichier = 'rapport-financier-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour que les accents s'affichent bien dans Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Rapport financier — Année ' . $data['anneeScolaire']], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['Indicateur', 'Valeur'], ';');
            fputcsv($handle, ['FCFA encaissés (année)', $data['totalEncaisseAnnee']], ';');
            fputcsv($handle, ['FCFA impayés (année)', $data['totalImpayeAnnee']], ';');
            fputcsv($handle, ['Taux de recouvrement', $data['tauxRecouvrement'] . '%'], ';');
            fputcsv($handle, ['Apprenants suivis', $data['nbApprenants']], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['Répartition par moyen de paiement'], ';');
            fputcsv($handle, ['Moyen', 'Pourcentage'], ';');
            foreach ($data['repartitionMoyens'] as $m) {
                fputcsv($handle, [$m['mode'], $m['pourcentage'] . '%'], ';');
            }
            fputcsv($handle, [], ';');

            fputcsv($handle, ['Recouvrement par classe'], ';');
            fputcsv($handle, ['Classe', 'Nb apprenants', 'Taux'], ';');
            foreach ($data['repartitionClasses'] as $c) {
                fputcsv($handle, [$c['nom'], $c['nb_apprenants'], $c['taux'] . '%'], ';');
            }

            fclose($handle);
        }, $nomFichier, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function genererDonneesRapport(): array
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

        return [
            'totalEncaisseAnnee' => $totalEncaisseAnnee,
            'totalImpayeAnnee'   => $totalImpayeAnnee,
            'tauxRecouvrement'   => $tauxRecouvrement,
            'nbApprenants'       => $nbApprenants,
            'repartitionMoyens'  => $repartitionMoyens,
            'repartitionClasses' => $repartitionClasses,
            'anneeScolaire'      => $anneeScolaire,
        ];
    }
}
