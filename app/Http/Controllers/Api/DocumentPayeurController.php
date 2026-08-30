<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
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

        $montantTotal = $apprenant->frais->sum('montant_total');
        $montantPaye  = $apprenant->frais->sum('montant_paye');
        $reste        = $montantTotal - $montantPaye;

        abort_if($reste > 0, 422, 'Impossible de générer le certificat : solde impayé sur cet apprenant.');

        $pourcentage   = $montantTotal > 0 ? round(($montantPaye / $montantTotal) * 100) : 100;
        $anneeScolaire = $apprenant->frais->first()->annee_scolaire ?? (now()->year . '-' . (now()->year + 1));

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
