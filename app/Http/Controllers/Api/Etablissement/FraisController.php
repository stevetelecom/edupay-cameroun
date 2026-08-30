<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Etablissement\FraisStoreRequest;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Echeancier;
use App\Models\FraisApprenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FraisController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Liste des catégories de frais de l'établissement.
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $categories = CategoriesFrais::where('etablissement_id', $etablissementId)
            ->with('echeanciers')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($c) => $this->formaterCategorie($c));

        return response()->json([
            'data'    => $categories,
            'classes' => Apprenant::where('etablissement_id', $etablissementId)
                ->distinct()->orderBy('classe')->pluck('classe'),
        ]);
    }

    /**
     * Crée une catégorie de frais (avec échéanciers optionnels).
     */
    public function store(FraisStoreRequest $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $validated       = $request->validated();

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etablissementId,
            'nom'              => $validated['nom'],
            'montant_total'    => $validated['montant_total'],
            'nb_tranches_max'  => $validated['nb_tranches_max'],
            'fractionnable'    => $request->boolean('fractionnable', true),
            'description'      => $validated['description'] ?? null,
            'annee_scolaire'   => $validated['annee_scolaire'],
            'actif'            => true,
        ]);

        if (! empty($validated['echeances'])) {
            foreach ($validated['echeances'] as $i => $ech) {
                Echeancier::create([
                    'categorie_frais_id' => $categorie->id,
                    'numero_tranche'     => $i + 1,
                    'libelle'            => $ech['libelle'] ?? 'Tranche ' . ($i + 1),
                    'montant'            => $ech['montant'],
                    'date_echeance'      => $ech['date_echeance'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Catégorie « ' . $categorie->nom . ' » créée avec succès.',
            'data'    => $this->formaterCategorie($categorie->load('echeanciers')),
        ], 201);
    }

    /**
     * Met à jour une catégorie de frais.
     */
    public function update(FraisStoreRequest $request, CategoriesFrais $frais): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        $validated = $request->validated();

        $frais->update([
            'nom'             => $validated['nom'],
            'montant_total'   => $validated['montant_total'],
            'nb_tranches_max' => $validated['nb_tranches_max'],
            'fractionnable'   => $request->boolean('fractionnable', $frais->fractionnable),
            'description'     => $validated['description'] ?? $frais->description,
            'annee_scolaire'  => $validated['annee_scolaire'],
        ]);

        return response()->json([
            'message' => 'Catégorie mise à jour.',
            'data'    => $this->formaterCategorie($frais->fresh(['echeanciers'])),
        ]);
    }

    /**
     * Supprime une catégorie de frais.
     */
    public function destroy(Request $request, CategoriesFrais $frais): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        $frais->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    /**
     * Affecte une catégorie de frais à une classe (ou à tous les apprenants actifs).
     */
    public function affecter(Request $request, CategoriesFrais $frais): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $this->autoriserFrais($frais);

        $validated = $request->validate([
            'classe' => 'nullable|string|max:50',
        ]);

        $apprenants = Apprenant::where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->when($validated['classe'] ?? null, fn ($q, $classe) => $q->where('classe', $classe))
            ->get();

        if ($apprenants->isEmpty()) {
            return response()->json([
                'message' => 'Aucun apprenant actif trouvé pour cette classe.',
            ], 422);
        }

        $ajoutes = 0;

        foreach ($apprenants as $apprenant) {
            $existe = FraisApprenant::where('apprenant_id', $apprenant->id)
                ->where('categorie_frais_id', $frais->id)
                ->where('annee_scolaire', $frais->annee_scolaire)
                ->exists();

            if ($existe) {
                continue;
            }

            FraisApprenant::create([
                'apprenant_id'       => $apprenant->id,
                'categorie_frais_id' => $frais->id,
                'montant_total'      => $frais->montant_total,
                'montant_paye'       => 0,
                'statut'             => 'impaye',
                'annee_scolaire'     => $frais->annee_scolaire,
            ]);

            $ajoutes++;
        }

        return response()->json([
            'message' => $ajoutes > 0
                ? 'Frais affectés à ' . $ajoutes . ' apprenant(s).'
                : 'Tous les apprenants de cette sélection ont déjà cette catégorie de frais.',
            'affectes' => $ajoutes,
        ]);
    }

    /**
     * Ajoute un échéancier à une catégorie de frais.
     */
    public function storeEcheancier(Request $request, CategoriesFrais $frais): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        $validated = $request->validate([
            'montant'       => ['required', 'numeric', 'min:0'],
            'date_echeance' => ['required', 'date'],
            'libelle'       => ['nullable', 'string', 'max:100'],
        ]);

        $numeroTranche = ($frais->echeanciers()->max('numero_tranche') ?? 0) + 1;

        $echeancier = Echeancier::create([
            'categorie_frais_id' => $frais->id,
            'numero_tranche'     => $numeroTranche,
            'montant'            => $validated['montant'],
            'date_echeance'      => $validated['date_echeance'],
            'libelle'            => $validated['libelle'] ?? 'Tranche ' . $numeroTranche,
        ]);

        return response()->json([
            'message' => 'Échéance ajoutée.',
            'data'    => [
                'id'            => $echeancier->id,
                'numero_tranche' => $echeancier->numero_tranche,
                'montant'       => (float) $echeancier->montant,
                'date_echeance' => $echeancier->date_echeance?->format('Y-m-d'),
                'libelle'       => $echeancier->libelle,
            ],
        ], 201);
    }

    /**
     * Supprime un échéancier.
     */
    public function destroyEcheancier(Request $request, CategoriesFrais $frais, Echeancier $echeancier): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        if ($echeancier->categorie_frais_id !== $frais->id) {
            return response()->json(['message' => 'Échéance invalide pour cette catégorie.'], 403);
        }

        $echeancier->delete();

        return response()->json(['message' => 'Échéance supprimée.']);
    }

    private function formaterCategorie(CategoriesFrais $c): array
    {
        return [
            'id'               => $c->id,
            'nom'              => $c->nom,
            'description'      => $c->description,
            'montant_total'    => (float) $c->montant_total,
            'nb_tranches_max'  => (int) $c->nb_tranches_max,
            'fractionnable'    => (bool) $c->fractionnable,
            'actif'            => (bool) $c->actif,
            'annee_scolaire'   => $c->annee_scolaire,
            'echeanciers'      => $c->echeanciers->map(fn ($e) => [
                'id'             => $e->id,
                'numero_tranche' => (int) $e->numero_tranche,
                'libelle'        => $e->libelle,
                'montant'        => (float) $e->montant,
                'date_echeance'  => $e->date_echeance?->format('Y-m-d'),
            ]),
        ];
    }

    private function autoriser(): int
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, 'Ce compte n\'a pas accès au back-office établissement.');
        }

        return $user->etablissement_id;
    }

    private function autoriserFrais(CategoriesFrais $frais): void
    {
        if ($frais->etablissement_id !== auth()->user()->etablissement_id) {
            abort(403, 'Catégorie de frais invalide pour cet établissement.');
        }
    }
}
