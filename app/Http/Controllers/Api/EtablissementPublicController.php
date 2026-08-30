<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\Paiement;
use Illuminate\Http\JsonResponse;

class EtablissementPublicController extends Controller
{
    /**
     * Statistiques globales EduPay (équivalent API de la landing page).
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'nb_etablissements' => Etablissement::where('statut', 'actif')->count(),
                'nb_apprenants'     => Apprenant::where('actif', true)->count(),
                'nb_paiements'      => Paiement::where('statut', 'valide')->count(),
                'montant_total'     => (int) Paiement::where('statut', 'valide')->sum('montant'),
            ],
        ]);
    }

    /**
     * Page publique d'un établissement (équivalent API de LandingController::etablissement).
     * Détail d'une école + ses catégories de frais actives + nb d'apprenants.
     */
    public function show(string $code): JsonResponse
    {
        $etablissement = Etablissement::where('code_etablissement', $code)->first();

        if (! $etablissement) {
            return response()->json(['message' => 'Établissement introuvable.'], 404);
        }

        $etablissement->load(['categoriesFrais' => function ($q) {
            $q->where('actif', true)->orderBy('nom');
        }]);

        $nbApprenants = Apprenant::where('etablissement_id', $etablissement->id)
            ->where('actif', true)
            ->count();

        return response()->json([
            'data' => [
                'id'                 => $etablissement->id,
                'code_etablissement' => $etablissement->code_etablissement,
                'nom'                => $etablissement->nom,
                'type'               => $etablissement->type,
                'statut_juridique'   => $etablissement->statut_juridique,
                'region'             => $etablissement->region,
                'ville'              => $etablissement->ville,
                'quartier'           => $etablissement->quartier,
                'telephone'          => $etablissement->telephone,
                'email'              => $etablissement->email,
                'site_web'           => $etablissement->site_web,
                'description'        => $etablissement->description,
                'logo'               => $etablissement->logo ? asset('storage/' . $etablissement->logo) : null,
                'nb_apprenants'      => $nbApprenants,
                'categories_frais'   => $etablissement->categoriesFrais->map(fn ($c) => [
                    'id'          => $c->id,
                    'nom'         => $c->nom,
                    'montant'     => (int) $c->montant,
                    'annee_scolaire' => $c->annee_scolaire,
                ])->values(),
            ],
        ]);
    }
}
