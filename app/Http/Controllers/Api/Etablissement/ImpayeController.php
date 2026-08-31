<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Resources\FraisResource;
use App\Mail\RelanceImpayeMail;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
     * Relance groupée à tous les parents ayant des impayés (par email).
     */
    public function relancerSms(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $anneeScolaire   = '2025-2026';

        $fraisImpayes = FraisApprenant::with('apprenant.parents')
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get();

        [$nbEnvoyes, $nbEchecs] = $this->envoyerRelancesGroupe($fraisImpayes);

        Log::channel('admin')->info("E07 relance groupée — Étab #{$etablissementId} (API) : {$nbEnvoyes} emails envoyés, {$nbEchecs} échecs.");

        return response()->json([
            'message'   => $nbEnvoyes > 0
                ? "{$nbEnvoyes} relance(s) envoyée(s) par email." . ($nbEchecs > 0 ? " ({$nbEchecs} échec(s))" : '')
                : 'Aucun email envoyé. Vérifiez les adresses email et les préférences de notification.',
            'envoi'     => [
                'envoyes' => $nbEnvoyes,
                'echecs'  => $nbEchecs,
            ],
        ], $nbEnvoyes > 0 ? 200 : 422);
    }

    /**
     * Relance pour un apprenant précis (par email).
     */
    public function relancerApprenant(Request $request, Apprenant $apprenant): JsonResponse
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

        [$nbEnvoyes, $nbEchecs] = $this->envoyerRelancesGroupe($fraisImpayes);

        return response()->json([
            'message' => $nbEnvoyes > 0
                ? "Relance envoyée à {$apprenant->prenom} {$apprenant->nom}."
                : 'Échec — vérifiez l\'email du parent ou ses préférences de notification.',
            'envoi'   => [
                'envoyes' => $nbEnvoyes,
                'echecs'  => $nbEchecs,
            ],
        ], $nbEnvoyes > 0 ? 200 : 422);
    }

    private function envoyerRelancesGroupe($fraisCollection): array
    {
        $nbEnvoyes = 0;
        $nbEchecs  = 0;

        foreach ($fraisCollection as $frais) {
            $reste = $frais->montant_total - $frais->montant_paye;
            if ($reste <= 0) {
                continue;
            }

            foreach ($frais->apprenant->parents as $parent) {
                if (! $parent->email || ! $parent->notif_email) {
                    $nbEchecs++;
                    continue;
                }

                try {
                    Mail::to($parent->email)->send(new RelanceImpayeMail(
                        $frais->apprenant,
                        $frais->categorieFrais->nom ?? 'frais scolaires',
                        (float) $reste,
                    ));
                    $nbEnvoyes++;
                } catch (\Throwable $e) {
                    $nbEchecs++;
                    Log::channel('admin')->error('E07 échec envoi relance email à ' . $parent->email . ' : ' . $e->getMessage());
                }
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
