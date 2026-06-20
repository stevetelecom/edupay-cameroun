<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\FraisApprenant;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpayeController extends Controller
{
    public function index(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;
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
            ->paginate(20)
            ->withQueryString();

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

        $tauxRecouvrement = $totalAttendu > 0
            ? round(($totalPaye / $totalAttendu) * 100)
            : 0;

        $classes = \App\Models\Apprenant::where('etablissement_id', $etablissementId)
            ->distinct()
            ->orderBy('classe')
            ->pluck('classe');

        return view('etablissement.impayes.index', compact(
            'fraisImpayes', 'totalImpaye', 'tauxRecouvrement', 'classes'
        ));
    }

    /**
     * Envoie un SMS de relance à tous les parents des apprenants en situation d'impayé.
     */
    public function relancerSms(Request $request, SmsService $smsService)
    {
        $etablissementId = Auth::user()->etablissement_id;
        $anneeScolaire   = '2025-2026';

        $fraisImpayes = FraisApprenant::with('apprenant.parents')
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get();

        $nbEnvoyes = 0;
        $nbEchecs  = 0;

        foreach ($fraisImpayes as $frais) {
            $reste = $frais->montant_total - $frais->montant_paye;

            foreach ($frais->apprenant->parents as $parent) {
                if (!$parent->telephone) {
                    continue;
                }

                $message = sprintf(
                    "EduPay: %s %s a un solde impaye de %s FCFA (%s). Merci de regulariser via l'app EduPay.",
                    $frais->apprenant->nom,
                    $frais->apprenant->prenom,
                    number_format($reste, 0, ',', ' '),
                    $frais->categorieFrais->nom ?? 'frais scolaires'
                );

                $envoye = $smsService->envoyerRelance($parent->telephone, $message);

                $envoye ? $nbEnvoyes++ : $nbEchecs++;
            }
        }

        Log::channel('admin')->info("Relance SMS impayés — Établissement #{$etablissementId} : {$nbEnvoyes} envoyés, {$nbEchecs} échecs.");

        return back()->with(
            $nbEnvoyes > 0 ? 'success' : 'error',
            $nbEnvoyes > 0
                ? "{$nbEnvoyes} SMS de relance envoyé(s) avec succès." . ($nbEchecs > 0 ? " ({$nbEchecs} échec(s))" : '')
                : "Aucun SMS n'a pu être envoyé. Vérifiez les numéros de téléphone des parents."
        );
    }
}
