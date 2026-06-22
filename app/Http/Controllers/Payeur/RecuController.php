<?php

namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecuController extends Controller
{
    /**
     * Affiche la page "Reçus & Certificats" (liste + génération).
     */
    public function index()
    {
        $user = Auth::user();

        $recus = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->where('user_id', $user->id)
            ->where('statut', 'valide')
            ->latest('date_paiement')
            ->get();

        $apprenants = $user->apprenants()
            ->with(['frais.categorieFrais', 'etablissement'])
            ->get();

        return view('payeur.recus', [
            'recus'      => $recus,
            'apprenants' => $apprenants,
            'pageTitle'  => 'Reçus & Certificats — EduPay',
        ]);
    }

    /**
     * Télécharge le reçu PDF d'un paiement validé.
     * Sécurité : un payeur ne peut télécharger que ses propres reçus.
     */
    public function telechargerRecu(Paiement $paiement): Response
    {
        abort_unless($paiement->user_id === Auth::id(), 403, 'Ce reçu ne vous appartient pas.');
        abort_unless($paiement->statut === 'valide', 404, 'Reçu indisponible pour un paiement non validé.');

        $paiement->load(['apprenant.etablissement', 'fraisApprenant.categorieFrais', 'user']);

        $pdf = Pdf::loadView('pdf.recu', ['paiement' => $paiement])
            ->setPaper('a4', 'portrait');

        return $pdf->download('Recu_' . $paiement->reference . '.pdf');
    }

    /**
     * Génère le certificat de scolarité (attestation de paiement à jour)
     * pour un apprenant. Refusé si l'apprenant a un solde impayé.
     */
    public function genererCertificat(Apprenant $apprenant): Response
    {
        $user = Auth::user();

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

        return $pdf->download('Certificat_' . Str::slug($apprenant->prenom . '-' . $apprenant->nom) . '.pdf');
    }
}
