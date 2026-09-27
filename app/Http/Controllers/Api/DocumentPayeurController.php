<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Génération serveur des documents PDF (reçus & certificats) pour l'app mobile.
 * On réutilise les vues Blade existantes du web — zéro duplication de template.
 */
class DocumentPayeurController extends Controller
{
    /**
     * Reçu PDF d'un paiement validé (propriétaire uniquement).
     */
    public function telechargerRecu(Paiement $paiement): Response
    {
        $user = auth()->user();

        abort_unless($paiement->user_id === $user->id, 403, 'Ce reçu ne vous appartient pas.');
        abort_unless($paiement->statut === 'valide', 404, 'Reçu indisponible pour un paiement non validé.');

        $paiement->load(['apprenant.etablissement', 'fraisApprenant.categorieFrais', 'user']);

        $pdf = Pdf::loadView('pdf.recu', ['paiement' => $paiement])
            ->setPaper('a4', 'portrait');

        return $this->reponsePdf($pdf->output(), 'Recu_' . $paiement->reference . '.pdf');
    }

    /**
     * Historique complet des paiements du payeur au format PDF.
     *
     * Le web l'expose via `payeur.historique?export=pdf`, mais c'est une route
     * web par cookie de session : inutilisable depuis l'app mobile, qui ne peut
     * télécharger qu'avec un jeton. Même vue Blade, mêmes données.
     *
     * Filtres optionnels : `du` et `au` (YYYY-MM-DD).
     */
    public function exporterHistorique(Request $request): Response
    {
        $validated = $request->validate([
            'du' => ['nullable', 'date_format:Y-m-d'],
            'au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:du'],
        ]);

        $user = auth()->user();

        $paiements = Paiement::with([
                'apprenant',
                'fraisApprenant.categorieFrais',
                'remboursements' => fn ($q) => $q->where('statut', 'valide'),
            ])
            ->where('user_id', $user->id)
            ->when(isset($validated['du']), fn ($q) => $q->whereDate('date_paiement', '>=', $validated['du']))
            ->when(isset($validated['au']), fn ($q) => $q->whereDate('date_paiement', '<=', $validated['au']))
            ->latest('date_paiement')
            ->get();

        $pdf = Pdf::loadView('pdf.historique_paiements', compact('paiements', 'user'))
            ->setPaper('a4', 'portrait');

        $suffixe = isset($validated['du']) || isset($validated['au'])
            ? '_'.($validated['du'] ?? 'debut').'_'.($validated['au'] ?? 'aujourdhui')
            : '';

        return $this->reponsePdf($pdf->output(), 'historique_edupay'.$suffixe.'_'.now()->format('Ymd').'.pdf');
    }

    /**
     * Certificat de scolarité PDF (attestation à jour) d'un apprenant rattaché.
     * Refusé si l'apprenant a un solde impayé.
     */
    public function genererCertificat(Apprenant $apprenant): Response
    {
        $user = auth()->user();

        abort_unless(
            $user->apprenants()->where('apprenants.id', $apprenant->id)->exists(),
            403,
            'Cet apprenant n\'est pas rattaché à votre compte.'
        );

        $apprenant->load(['etablissement', 'frais.categorieFrais']);

        // Aucun frais assigné → pas d'attestation possible
        abort_if($apprenant->frais->isEmpty(), 422, 'Aucun frais n\'est assigné à cet apprenant. L\'attestation est impossible.');

        $anneeScolaire = $apprenant->frais->first()->annee_scolaire ?? (now()->year . '-' . (now()->year + 1));

        // On se limite à l'année scolaire du dossier courant pour la cohérence de l'attestation
        $fraisAnnee = $apprenant->frais->where('annee_scolaire', $anneeScolaire);
        $montantTotal = $fraisAnnee->sum('montant_total');
        $montantPaye  = $fraisAnnee->sum('montant_paye');
        $reste        = $montantTotal - $montantPaye;

        abort_if($reste > 0, 422, 'Impossible de générer le certificat : solde impayé sur cet apprenant.');

        $pourcentage = $montantTotal > 0 ? round(($montantPaye / $montantTotal) * 100) : 100;

        $pdf = Pdf::loadView('pdf.certificat', [
            'apprenant'     => $apprenant,
            'montantTotal'  => $montantTotal,
            'montantPaye'   => $montantPaye,
            'pourcentage'   => $pourcentage,
            'anneeScolaire' => $anneeScolaire,
        ])->setPaper('a4', 'portrait');

        return $this->reponsePdf($pdf->output(), 'Certificat_' . Str::slug($apprenant->prenom . '-' . $apprenant->nom) . '.pdf');
    }

    /**
     * Renvoie le PDF en tant que fichier binaire téléchargeable (Outrepassable),
     * compatible avec les clients HTTP mobiles (Flutter/Dio, React Native fetch).
     */
    private function reponsePdf(string $contenu, string $filename): Response
    {
        return response($contenu, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => (string) strlen($contenu),
            'Cache-Control'       => 'private, no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }
}
