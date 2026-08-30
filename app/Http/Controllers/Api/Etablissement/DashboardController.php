<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Resources\EtablissementResource;
use App\Http\Resources\PaiementResource;
use App\Models\Abonnement;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Statistiques du back-office établissement (équivalent web DashboardController::index).
     * Réservé aux comptes rattachés à un établissement (directeur / comptable / caissier).
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            return response()->json([
                'message' => 'Ce compte n\'a pas accès au back-office établissement.',
            ], 403);
        }

        $etablissementId = $user->etablissement_id;
        $etablissement   = $user->etablissement;
        $anneeScolaire   = '2025-2026';

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
        $nbApprenants = Apprenant::where('etablissement_id', $etablissementId)
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

        // 5 derniers paiements reçus
        $derniersPaiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->latest('date_paiement')
            ->take(5)
            ->get();

        // Abonnement actif
        $abonnement = Abonnement::where('etablissement_id', $etablissementId)
            ->whereIn('statut', ['actif', 'grace_period'])
            ->latest()
            ->first();

        return response()->json([
            'data' => [
                'etablissement'         => new EtablissementResource($etablissement),
                'annee_scolaire'        => $anneeScolaire,
                'abonnement'            => $abonnement ? [
                    'statut'       => $abonnement->statut,
                    'plan'         => $abonnement->plan,
                    'date_fin'     => $abonnement->date_fin?->toDateString(),
                    'grace_period_fin' => $abonnement->grace_period_fin?->toDateString(),
                    'est_actif'    => $abonnement->estActif(),
                ] : null,
                'kpis' => [
                    'total_encaisse_mois'    => (int) $totalEncaisseMois,
                    'total_impaye'           => (int) $totalImpaye,
                    'nb_apprenants'          => (int) $nbApprenants,
                    'nb_dossiers_impayes'    => (int) $nbDossiersImpayes,
                    'taux_recouvrement'      => (int) $tauxRecouvrementDecimal,
                    'taux_recouvrement_pct'  => $tauxRecouvrementDecimal,
                    'total_attendu'          => (int) $totalAttendu,
                    'total_paye'             => (int) $totalPaye,
                ],
                'derniers_paiements'    => PaiementResource::collection($derniersPaiements),
            ],
        ]);
    }
}
