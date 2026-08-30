<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RapportController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Rapport financier de l'établissement (équivalent web RapportController::genererDonneesRapport).
     */
    public function index(Request $request): JsonResponse
    {
        $this->autoriser();
        $data = $this->genererDonneesRapport();

        return response()->json([
            'data' => [
                'annee_scolaire'       => $data['anneeScolaire'],
                'total_encaisse_annee' => (int) $data['totalEncaisseAnnee'],
                'total_impaye_annee'   => (int) $data['totalImpayeAnnee'],
                'total_attendu'        => (int) $data['totalAttendu'],
                'taux_recouvrement'    => (int) $data['tauxRecouvrement'],
                'nb_apprenants'        => (int) $data['nbApprenants'],
                'repartition_moyens'   => collect($data['repartitionMoyens'])->values(),
                'repartition_classes'  => collect($data['repartitionClasses'])->values(),
            ],
        ]);
    }

    /**
     * Export PDF du rapport financier (réutilise la vue pdf.rapport du web).
     */
    public function exportPdf(Request $request): Response
    {
        $this->autoriser();
        $data = $this->genererDonneesRapport();

        $pdf = Pdf::loadView('pdf.rapport', $data);

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport-financier-' . now()->format('Y-m-d') . '.pdf"',
            'Content-Length'      => (string) strlen($pdf->output()),
        ]);
    }

    /**
     * Export CSV du rapport financier (compatible Excel, BOM UTF-8).
     */
    public function exportExcel(Request $request): Response
    {
        $this->autoriser();
        $data = $this->genererDonneesRapport();

        $contenu = '';
        $contenu .= "\xEF\xBB\xBF"; // BOM UTF-8 pour les accents dans Excel
        $contenu .= implode(';', ['Rapport financier — Année ' . $data['anneeScolaire']]) . "\r\n";
        $contenu .= "\r\n";
        $contenu .= implode(';', ['Indicateur', 'Valeur']) . "\r\n";
        $contenu .= implode(';', ['FCFA encaissés (année)', $data['totalEncaisseAnnee']]) . "\r\n";
        $contenu .= implode(';', ['FCFA impayés (année)', $data['totalImpayeAnnee']]) . "\r\n";
        $contenu .= implode(';', ['Taux de recouvrement', $data['tauxRecouvrement'] . '%']) . "\r\n";
        $contenu .= implode(';', ['Apprenants suivis', $data['nbApprenants']]) . "\r\n";
        $contenu .= "\r\n";
        $contenu .= implode(';', ['Répartition par moyen de paiement']) . "\r\n";
        $contenu .= implode(';', ['Moyen', 'Pourcentage']) . "\r\n";
        foreach ($data['repartitionMoyens'] as $m) {
            $contenu .= implode(';', [$m['mode'], $m['pourcentage'] . '%']) . "\r\n";
        }
        $contenu .= "\r\n";
        $contenu .= implode(';', ['Recouvrement par classe']) . "\r\n";
        $contenu .= implode(';', ['Classe', 'Nb apprenants', 'Taux']) . "\r\n";
        foreach ($data['repartitionClasses'] as $c) {
            $contenu .= implode(';', [$c['nom'], $c['nb_apprenants'], $c['taux'] . '%']) . "\r\n";
        }

        return response($contenu, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rapport-financier-' . now()->format('Y-m-d') . '.csv"',
            'Content-Length'      => (string) strlen($contenu),
        ]);
    }

    private function genererDonneesRapport(): array
    {
        $etablissementId = auth()->user()->etablissement_id;
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

        $totalValideTous = $totalEncaisseAnnee;

        $repartitionMoyens = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->selectRaw('mode_paiement, SUM(montant) as total')
            ->groupBy('mode_paiement')
            ->get()
            ->map(fn ($row) => [
                'mode'        => $row->mode_paiement,
                'pourcentage' => $totalValideTous > 0 ? round(($row->total / $totalValideTous) * 100) : 0,
                'total'       => (int) $row->total,
            ])
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
                    'nb_apprenants' => (int) $row->nb_apprenants,
                    'taux'          => $attendu > 0 ? round(($paye / $attendu) * 100) : 0,
                ];
            })
            ->toArray();

        return [
            'totalEncaisseAnnee' => (int) $totalEncaisseAnnee,
            'totalImpayeAnnee'   => (int) $totalImpayeAnnee,
            'totalAttendu'       => (int) $totalAttendu,
            'tauxRecouvrement'   => (int) $tauxRecouvrement,
            'nbApprenants'       => (int) $nbApprenants,
            'repartitionMoyens'  => $repartitionMoyens,
            'repartitionClasses' => $repartitionClasses,
            'anneeScolaire'      => $anneeScolaire,
        ];
    }

    private function autoriser(): int
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, 'Ce compte n\'a pas accès au back-office établissement.');
        }

        return $user->etablissement_id;
    }
}
