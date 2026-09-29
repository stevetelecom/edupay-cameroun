<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\AnneeScolaire;
use App\Support\GraphiquesSvg;

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

        // Graphiques rendus en SVG statique (DomPDF n'exécute pas JS) :
        // mêmes données et mêmes couleurs que les charts du dashboard.
        $data['svgVenn'] = GraphiquesSvg::venn(
            $data['venn'],
            $data['nbApprenantsMultiMoyens'],
            [
                'mtn_momo'     => __('etablissement.mt_mtn'),
                'orange_money' => __('etablissement.mt_orange'),
                'carte'        => __('etablissement.carte'),
            ]
        );
        $data['svgClasses'] = GraphiquesSvg::barresClasses($data['repartitionClasses']);

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

            // ── Venn : apprenants par moyen (exclusifs) + multi-moyens ──
            fputcsv($handle, ['Répartition des paiements par moyen (Venn)'], ';');
            fputcsv($handle, ['Moyen', 'Apprenants (exclusifs)'], ';');
            foreach ($data['repartitionMoyens'] as $m) {
                $libelle = match ($m['mode']) {
                    'mtn_momo'     => 'MTN MoMo',
                    'orange_money' => 'Orange Money',
                    'carte'        => 'Carte bancaire',
                    default        => $m['mode'],
                };
                fputcsv($handle, [$libelle, $data['venn'][$m['mode']] ?? 0], ';');
            }
            fputcsv($handle, ['Multi-moyens (2 moyens ou plus)', $data['nbApprenantsMultiMoyens']], ';');
            // Détail du chevauchement : apprenants groupés par nombre de moyens utilisés
            foreach (($data['nbModesParApprenant'] ?? collect()) as $nbModes => $effectif) {
                fputcsv($handle, ['Apprenants avec ' . $nbModes . ' moyen(s)', $effectif], ';');
            }
            fputcsv($handle, [], ';');

            fputcsv($handle, ['Recouvrement par classe'], ';');
            fputcsv($handle, ['Classe', 'Nb apprenants', 'Attendu (FCFA)', 'Encaissé (FCFA)', 'Taux'], ';');
            foreach ($data['repartitionClasses'] as $c) {
                fputcsv($handle, [
                    $c['nom'],
                    $c['nb_apprenants'],
                    $c['attendu'] ?? 0,
                    $c['paye'] ?? 0,
                    $c['taux'] . '%',
                ], ';');
            }

            fclose($handle);
        }, $nomFichier, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function genererDonneesRapport(): array
    {
        $etablissementId = Auth::user()->etablissement_id;
        $anneeScolaire   = AnneeScolaire::active();

        // Filtré sur l'année scolaire ACTIVE — jamais un cumul toutes-années,
        // pour que le rapport reflète strictement l'année en cours.
        $totalEncaisseAnnee = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
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

        // Apprenants ayant un dossier de frais sur l'année active uniquement
        $nbApprenants = Apprenant::where('etablissement_id', $etablissementId)
            ->whereHas('frais', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
            ->count();

        $totalValideTous = $totalEncaisseAnnee;

        $repartitionMoyens = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
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

        // ── Diagramme de Venn : chevauchement des moyens de paiement ──
        // Un apprenant peut payer avec plusieurs moyens sur l'année : le
        // diagramme de Venn montre les « exclusifs » (un seul moyen) et le
        // cœur « multi-moyens » (au moins deux moyens différents).
        $apprenantsMultiMoyens = Paiement::where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
            ->selectRaw('apprenant_id, COUNT(DISTINCT mode_paiement) as nb_modes')
            ->groupBy('apprenant_id')
            ->get();
        $idsMultiMoyens = $apprenantsMultiMoyens->where('nb_modes', '>=', 2)->pluck('apprenant_id');
        $nbApprenantsMultiMoyens = $idsMultiMoyens->count();
        // Détail du chevauchement pour l'export CSV : combien d'apprenants
        // ont payé avec 1, 2 ou 3 moyens distincts.
        $nbModesParApprenant = $apprenantsMultiMoyens
            ->groupBy('nb_modes')
            ->map(fn ($g) => $g->count());
        $venn = [];
        foreach (['mtn_momo', 'orange_money', 'carte'] as $modeVenn) {
            $venn[$modeVenn] = Paiement::where('statut', 'valide')
                ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
                ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
                ->where('mode_paiement', $modeVenn)
                ->when($idsMultiMoyens->isNotEmpty(), fn ($q) => $q->whereNotIn('apprenant_id', $idsMultiMoyens))
                ->distinct('apprenant_id')
                ->count('apprenant_id');
        }

        $repartitionClasses = Apprenant::where('etablissement_id', $etablissementId)
            ->selectRaw('classe, COUNT(*) as nb_apprenants')
            ->groupBy('classe')
            ->orderBy('classe')
            ->get()
            ->map(function ($row) use ($anneeScolaire, $etablissementId) {
                // R : le filtre portait sur la CLASSE SEULE. Deux etablissements
                // partageant le meme nom de classe (CM2, 6eme...) voyaient leurs
                // frais additionnes : le taux de recouvrement de ma classe
                // includait les encaissements des autres ecoles, et les
                // montants d'autrui etaient lisibles par soustraction.
                $frais = FraisApprenant::where('annee_scolaire', $anneeScolaire)
                    ->whereHas('apprenant', fn ($q) => $q
                        ->where('classe', $row->classe)
                        ->where('etablissement_id', $etablissementId))
                    ->get();

                $attendu = $frais->sum('montant_total');
                $paye    = $frais->sum('montant_paye');

                return [
                    'nom'           => $row->classe,
                    'nb_apprenants' => $row->nb_apprenants,
                    'taux'          => $attendu > 0 ? round(($paye / $attendu) * 100) : 0,
                    'attendu'       => (int) $attendu,
                    'paye'          => (int) $paye,
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
            'venn'               => $venn,
            'nbApprenantsMultiMoyens' => $nbApprenantsMultiMoyens,
            'nbModesParApprenant' => $nbModesParApprenant,
            'tickPasClasse'      => 100000,
            'anneeScolaire'      => $anneeScolaire,
        ];
    }
}
