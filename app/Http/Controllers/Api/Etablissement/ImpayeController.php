<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Resources\FraisResource;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImpayeController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Liste des frais impayés de l'établissement + synthèse (équivalent web ImpayeController::index).
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $anneeScolaire   = '2025-2026';

        $fraisImpayes = FraisApprenant::with(['apprenant', 'categorieFrais'])
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', function ($q) use ($etablissementId, $request) {
                $q->where('etablissement_id', $etablissementId);
                if ($request->filled('classe')) {
                    $q->where('classe', $request->classe);
                }
            })
            ->orderByDesc('montant_total')
            ->paginate($request->integer('per_page', 20));

        $totalImpaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get()
            ->sum(fn ($f) => $f->montant_total - $f->montant_paye);

        $totalAttendu = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_total');

        $totalPaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_paye');

        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100) : 0;

        return response()->json([
            'data' => [
                'synthese' => [
                    'total_impaye'      => (int) $totalImpaye,
                    'total_attendu'     => (int) $totalAttendu,
                    'total_paye'        => (int) $totalPaye,
                    'taux_recouvrement' => (int) $tauxRecouvrement,
                ],
                'frais_impayes' => FraisResource::collection($fraisImpayes),
                'pagination'    => [
                    'current_page' => $fraisImpayes->currentPage(),
                    'last_page'    => $fraisImpayes->lastPage(),
                    'total'        => $fraisImpayes->total(),
                    'per_page'     => $fraisImpayes->perPage(),
                ],
                'classes' => Apprenant::where('etablissement_id', $etablissementId)
                    ->distinct()->orderBy('classe')->pluck('classe'),
            ],
        ]);
    }

    /**
     * Relance SMS groupée à tous les parents ayant des impayés.
     */
    public function relancerSms(Request $request, SmsService $smsService): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $anneeScolaire   = '2025-2026';

        $fraisImpayes = FraisApprenant::with('apprenant.parents')
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get();

        [$nbEnvoyes, $nbEchecs] = $this->envoyerRelancesGroupe($fraisImpayes, $smsService);

        Log::channel('admin')->info("E07 relance groupée — Étab #{$etablissementId} (API) : {$nbEnvoyes} envoyés, {$nbEchecs} échecs.");

        return response()->json([
            'message'   => $nbEnvoyes > 0
                ? "{$nbEnvoyes} SMS envoyé(s)." . ($nbEchecs > 0 ? " ({$nbEchecs} échec(s))" : '')
                : 'Aucun SMS envoyé. Vérifiez les numéros et les préférences de notification.',
            'envoi'     => [
                'envoyes' => $nbEnvoyes,
                'echecs'  => $nbEchecs,
            ],
        ], $nbEnvoyes > 0 ? 200 : 422);
    }

    /**
     * Relance SMS pour un apprenant précis.
     */
    public function relancerApprenant(Request $request, Apprenant $apprenant, SmsService $smsService): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        $fraisImpayes = FraisApprenant::with('apprenant.parents')
            ->where('apprenant_id', $apprenant->id)
            ->where('annee_scolaire', '2025-2026')
            ->where('statut', '!=', 'regle')
            ->get();

        [$nbEnvoyes, $nbEchecs] = $this->envoyerRelancesGroupe($fraisImpayes, $smsService);

        return response()->json([
            'message' => $nbEnvoyes > 0
                ? "SMS de relance envoyé à {$apprenant->prenom} {$apprenant->nom}."
                : 'Échec — vérifiez le numéro du parent ou ses préférences SMS.',
            'envoi'   => [
                'envoyes' => $nbEnvoyes,
                'echecs'  => $nbEchecs,
            ],
        ], $nbEnvoyes > 0 ? 200 : 422);
    }

    private function envoyerRelancesGroupe($fraisCollection, SmsService $smsService): array
    {
        $nbEnvoyes = 0;
        $nbEchecs  = 0;

        foreach ($fraisCollection as $frais) {
            $reste = $frais->montant_total - $frais->montant_paye;
            if ($reste <= 0) {
                continue;
            }

            foreach ($frais->apprenant->parents as $parent) {
                if (! $parent->telephone) {
                    $nbEchecs++;
                    continue;
                }

                $message = sprintf(
                    "EduPay: %s %s - solde impaye %s FCFA (%s). Regularisez sur l'app EduPay Cameroun.",
                    $frais->apprenant->nom,
                    $frais->apprenant->prenom,
                    number_format($reste, 0, ',', ' '),
                    $frais->categorieFrais->nom ?? 'frais scolaires'
                );

                $smsService->envoyerRelance($parent->telephone, $message)
                    ? $nbEnvoyes++
                    : $nbEchecs++;
            }
        }

        return [$nbEnvoyes, $nbEchecs];
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
