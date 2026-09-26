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
use App\Support\AnneeScolaire;

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
        $anneeScolaire   = AnneeScolaire::active($etablissement);

        // Total encaissé ce mois — filtré sur l'année scolaire ACTIVE via
        // frais_apprenant.annee_scolaire (miroir web) pour ne jamais mélanger
        // avec des paiements liés à une année passée conservée en base.
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
        $nbApprenants = Apprenant::where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->count();

        // Dossiers de frais ouverts sur l'année active (0 => indicateurs vides)
        $nbFraisAnnee = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->count();

        // Nombre de dossiers de frais impayés
        $nbDossiersImpayes = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->count();

        // Encaissements du jour (contrat mobile data.encaissements_jour)
        $totalEncaisseJour = Paiement::where('statut', 'valide')
            ->whereDate('date_paiement', now()->toDateString())
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeScolaire))
            ->sum('montant');

        // Repartition des apprenants par statut de paiement (contrat mobile
        // payants / partiels / impayes). Les compteurs portent sur des
        // apprenants, comme `total_apprenants` : ils doivent s'additionner.
        $repartitionStatuts = Apprenant::where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->selectRaw('statut_paiement, COUNT(*) as total')
            ->groupBy('statut_paiement')
            ->pluck('total', 'statut_paiement');

        $nbPayants  = (int) ($repartitionStatuts['regle'] ?? 0);
        $nbPartiels = (int) ($repartitionStatuts['partiel'] ?? 0);
        $nbImpayes  = (int) ($repartitionStatuts['impaye'] ?? 0);

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
            // Audit G : le contrat mobile (docs/DOCUMENTATION_API.md) lit les
            // compteurs a plat dans `data`. Ils etaient uniquement imbriques
            // sous `data.kpis`, donc l'ecran affichait 0 et un titre vide.
            // Les deux presentations sont exposees : l'plate pour le mobile,
            // `kpis` pour le back-office web et les anciens clients.
            'success' => true,
            'data' => [
                'encaissements_jour'     => (int) $totalEncaisseJour,
                'encaissements_mois'     => (int) $totalEncaisseMois,
                'taux_recouvrement'      => $tauxRecouvrementDecimal,
                'total_apprenants'       => (int) $nbApprenants,
                'payants'                => $nbPayants,
                'partiels'               => $nbPartiels,
                'impayes'                => $nbImpayes,
                'transactions_recentes'  => PaiementResource::collection($derniersPaiements),

                'etablissement'         => new EtablissementResource($etablissement),
                'annee_scolaire'        => $anneeScolaire,
                'nb_frais_annee'        => (int) $nbFraisAnnee,
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

    /**
     * Abonnement actuel de l'établissement + listes des plans disponibles.
     */
    public function abonnement(): JsonResponse
    {
        $user = auth()->user();
        $this->autoriser();

        $etablissementId = $user->etablissement_id;

        $abonnement = Abonnement::where('etablissement_id', $etablissementId)
            ->whereIn('statut', ['actif', 'grace_period'])
            ->latest()
            ->first();

        $planActuel = $abonnement?->plan;

        return response()->json([
            'data' => [
                'abonnement'   => $abonnement ? [
                    'statut'             => $abonnement->statut,
                    'plan'               => $planActuel,
                    'plan_nom'           => \App\Models\Abonnement::PLANS[$planActuel]['nom'] ?? null,
                    'date_debut'         => $abonnement->created_at?->toDateString(),
                    'date_fin'           => $abonnement->date_fin?->toDateString(),
                    'grace_period_fin'   => $abonnement->grace_period_fin?->toDateString(),
                    'est_actif'          => $abonnement->estActif(),
                    'en_grace_period'    => $abonnement->enGracePeriod(),
                    'jours_restants'     => $abonnement->joursRestants(),
                ] : null,
                'plans'        => collect(\App\Models\Abonnement::PLANS)
                    ->map(fn ($p, $slug) => [
                        'slug'            => $slug,
                        'nom'             => $p['nom'],
                        'montant'         => $p['montant'],
                        'max_apprenants'  => $p['max_apprenants'],
                        'sms_mensuel'     => $p['sms_mensuel'],
                        'multi_sites'     => $p['multi_sites'],
                        'exports_cobac'   => $p['exports_cobac'],
                        'actuel'          => $slug === $planActuel,
                    ])->values(),
            ],
        ]);
    }

    private function autoriser(): void
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, 'Ce compte n\'a pas accès au back-office établissement.');
        }
    }
}
