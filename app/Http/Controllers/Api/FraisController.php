<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FraisResource;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use Illuminate\Http\JsonResponse;

class FraisController extends Controller
{
    /**
     * Liste des frais d'un apprenant rattaché à l'utilisateur connecté.
     * Bloque l'accès si le rattachement n'est pas validé par l'établissement.
     */
    public function index(Apprenant $apprenant): JsonResponse
    {
        $user = auth()->user();

        $apprenant = $user->apprenants()
            ->where('apprenants.id', $apprenant->id)
            ->with(['etablissement', 'frais.categorieFrais.echeanciers'])
            ->first();

        if (! $apprenant) {
            return response()->json(['message' => 'Cet apprenant ne vous est pas rattaché.'], 403);
        }

        if (! $apprenant->valide_par_etablissement) {
            return response()->json([
                'message' => 'Ce rattachement est en attente de validation par l\'établissement. Vous serez notifié dès qu\'il sera confirmé.',
            ], 403);
        }

        return response()->json([
            'data' => FraisResource::collection($apprenant->frais),
        ]);
    }

    /**
     * Détail d'un dossier de frais précis (équivalent web Payeur\PaiementController::show).
     * Réservé au parent du dossier, rattachement validé requis.
     */
    public function show(FraisApprenant $fraisApprenant): JsonResponse
    {
        $user = auth()->user();

        $estParent = $user->apprenants()
            ->where('apprenants.id', $fraisApprenant->apprenant_id)
            ->exists();

        if (! $estParent) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à accéder à ce dossier de paiement.'], 403);
        }

        $fraisApprenant->load(['categorieFrais.echeanciers', 'apprenant.etablissement', 'paiements' => fn ($q) => $q->latest()]);

        if ($fraisApprenant->apprenant && ! $fraisApprenant->apprenant->valide_par_etablissement) {
            return response()->json([
                'message' => 'Ce rattachement est en attente de validation par l\'établissement.',
            ], 403);
        }

        $reste = (float) $fraisApprenant->montant_total - (float) $fraisApprenant->montant_paye;

        return response()->json([
            'data' => [
                'id'             => $fraisApprenant->id,
                'apprenant'      => [
                    'id'     => $fraisApprenant->apprenant?->id,
                    'nom'    => $fraisApprenant->apprenant?->nom,
                    'prenom' => $fraisApprenant->apprenant?->prenom,
                    'classe' => $fraisApprenant->apprenant?->classe,
                    'etablissement' => [
                        'id'    => $fraisApprenant->apprenant?->etablissement?->id,
                        'nom'   => $fraisApprenant->apprenant?->etablissement?->nom,
                        'ville' => $fraisApprenant->apprenant?->etablissement?->ville,
                    ],
                ],
                'categorie'      => $fraisApprenant->categorieFrais ? [
                    'id'            => $fraisApprenant->categorieFrais->id,
                    'nom'           => $fraisApprenant->categorieFrais->nom,
                    'description'   => $fraisApprenant->categorieFrais->description,
                    'montant_total' => (float) $fraisApprenant->categorieFrais->montant_total,
                    'nb_tranches_max' => (int) $fraisApprenant->categorieFrais->nb_tranches_max,
                    'fractionnable' => (bool) $fraisApprenant->categorieFrais->fractionnable,
                    'annee_scolaire' => $fraisApprenant->categorieFrais->annee_scolaire,
                ] : null,
                'montant_total'  => (float) $fraisApprenant->montant_total,
                'montant_paye'   => (float) $fraisApprenant->montant_paye,
                'reste'          => $reste,
                'statut'         => $fraisApprenant->statut,
                'annee_scolaire' => $fraisApprenant->annee_scolaire,
                'echeanciers'    => $fraisApprenant->categorieFrais?->echeanciers
                    ? \App\Http\Resources\EcheancierResource::collection($fraisApprenant->categorieFrais->echeanciers)
                    : [],
                'paiements'      => $fraisApprenant->paiements->map(fn ($p) => [
                    'id'             => $p->id,
                    'reference'      => $p->reference,
                    'montant'        => (float) $p->montant,
                    'statut'         => $p->statut,
                    'mode_paiement'  => $p->mode_paiement,
                    'date_paiement'  => $p->date_paiement?->toISOString(),
                ]),
            ],
        ]);
    }
}
