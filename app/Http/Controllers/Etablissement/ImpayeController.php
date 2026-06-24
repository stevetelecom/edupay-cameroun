<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
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
            ->whereHas('apprenant', fn($q) => $q->where('etablissement_id', $etablissementId))
            ->get()
            ->sum(fn($f) => $f->montant_total - $f->montant_paye);

        $totalAttendu = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_total');

        $totalPaye = FraisApprenant::where('annee_scolaire', $anneeScolaire)
            ->whereHas('apprenant', fn($q) => $q->where('etablissement_id', $etablissementId))
            ->sum('montant_paye');

        $tauxRecouvrement = $totalAttendu > 0
            ? round(($totalPaye / $totalAttendu) * 100)
            : 0;

        $classes = Apprenant::where('etablissement_id', $etablissementId)
            ->distinct()->orderBy('classe')->pluck('classe');

        return view('etablissement.impayes.index', compact(
            'fraisImpayes', 'totalImpaye', 'tauxRecouvrement', 'classes'
        ));
    }

    public function relancerSms(Request $request, SmsService $smsService)
    {
        $etablissementId = Auth::user()->etablissement_id;
        $anneeScolaire   = '2025-2026';

        $fraisImpayes = FraisApprenant::with('apprenant.parents')
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn($q) => $q->where('etablissement_id', $etablissementId))
            ->get();

        [$nbEnvoyes, $nbEchecs] = $this->envoyerRelancesGroupe($fraisImpayes, $smsService);

        Log::channel('admin')->info("E07 relance groupée — Étab #{$etablissementId} : {$nbEnvoyes} envoyés, {$nbEchecs} échecs.");

        return back()->with(
            $nbEnvoyes > 0 ? 'success' : 'error',
            $nbEnvoyes > 0
                ? "{$nbEnvoyes} SMS envoyé(s)." . ($nbEchecs > 0 ? " ({$nbEchecs} échec(s))" : '')
                : 'Aucun SMS envoyé. Vérifiez les numéros et les préférences de notification.'
        );
    }

    public function relancerApprenant(Apprenant $apprenant, SmsService $smsService)
    {
        if ($apprenant->etablissement_id !== Auth::user()->etablissement_id) {
            abort(403);
        }

        $fraisImpayes = FraisApprenant::with('apprenant.parents')
            ->where('apprenant_id', $apprenant->id)
            ->where('annee_scolaire', '2025-2026')
            ->where('statut', '!=', 'regle')
            ->get();

        [$nbEnvoyes, $nbEchecs] = $this->envoyerRelancesGroupe($fraisImpayes, $smsService);

        return back()->with(
            $nbEnvoyes > 0 ? 'success' : 'error',
            $nbEnvoyes > 0
                ? "SMS de relance envoyé à {$apprenant->prenom} {$apprenant->nom}."
                : 'Échec — vérifiez le numéro du parent ou ses préférences SMS.'
        );
    }

    private function envoyerRelancesGroupe($fraisCollection, SmsService $smsService): array
    {
        $nbEnvoyes = 0;
        $nbEchecs  = 0;

        foreach ($fraisCollection as $frais) {
            $reste = $frais->montant_total - $frais->montant_paye;
            if ($reste <= 0) continue;

            foreach ($frais->apprenant->parents as $parent) {
                if (!$parent->telephone) { $nbEchecs++; continue; }

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
}
