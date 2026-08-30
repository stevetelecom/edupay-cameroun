<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Rapport financier de l'établissement (équivalent web RapportController::genererDonneesRapport).
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
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
            ]);

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
            });

        return response()->json([
            'data' => [
                'annee_scolaire'       => $anneeScolaire,
                'total_encaisse_annee' => (int) $totalEncaisseAnnee,
                'total_impaye_annee'   => (int) $totalImpayeAnnee,
                'total_attendu'        => (int) $totalAttendu,
                'taux_recouvrement'    => (int) $tauxRecouvrement,
                'nb_apprenants'        => (int) $nbApprenants,
                'repartition_moyens'   => $repartitionMoyens->values(),
                'repartition_classes'  => $repartitionClasses->values(),
            ],
        ]);
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
