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
            'actif'            => $request->boolean('actif', true),
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
            // exposée en lecture par formaterCategorie() : sans cela le mobile
            // ne pouvait pas appliquer le conseil de destroy() (« désactiver »)
            'actif'           => $request->boolean('actif', $frais->actif),
            'description'     => $validated['description'] ?? $frais->description,
            'annee_scolaire'  => $validated['annee_scolaire'],
        ]);

        $this->synchroniserEcheanciers($frais);

        return response()->json([
            'message' => 'Catégorie mise à jour.',
            'data'    => $this->formaterCategorie($frais->fresh(['echeanciers'])),
        ]);
    }

    /**
     * Supprime une catégorie de frais.
     * 🔒 Protégée si des paiements existent ET que c'est l'année active — pour
     * une année passée, suppression cascade complète autorisée (miroir web).
     */
    public function destroy(Request $request, CategoriesFrais $frais): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        $anneeActive    = \App\Support\AnneeScolaire::active($frais->etablissement);
        $estAnneeActive = $frais->annee_scolaire === $anneeActive;

        $aDesPaiements = \App\Models\Paiement::whereHas(
            'fraisApprenant',
            fn ($q) => $q->where('categorie_frais_id', $frais->id)
        )->exists();

        if ($aDesPaiements && $estAnneeActive) {
            return response()->json([
                'message' => 'Impossible de supprimer « ' . $frais->nom . ' » : des paiements sont déjà enregistrés pour cette catégorie active. Vous pouvez la désactiver à la place.',
            ], 422);
        }

        $this->supprimerCascade($frais);

        return response()->json(['message' => 'Catégorie « ' . $frais->nom . ' » supprimée.']);
    }

    /**
     * Duplique une catégorie de frais (+ ses échéances) vers une nouvelle année
     * scolaire. Ne copie PAS les FraisApprenant (chaque année repart à 0 et devra
     * être ré-affectée via /frais/{frais}/affecter).
     */
    public function dupliquer(Request $request, CategoriesFrais $frais): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        $validated = $request->validate([
            'nouvelle_annee_scolaire' => ['required', 'string', 'max:20'],
        ]);

        $nouvelleAnnee = $validated['nouvelle_annee_scolaire'];

        $dejaExistante = CategoriesFrais::where('etablissement_id', $frais->etablissement_id)
            ->where('nom', $frais->nom)
            ->where('annee_scolaire', $nouvelleAnnee)
            ->exists();

        if ($dejaExistante) {
            return response()->json([
                'message' => 'Une catégorie « ' . $frais->nom . ' » existe déjà pour l\'année ' . $nouvelleAnnee . '.',
            ], 422);
        }

        $nouvelleCategorie = CategoriesFrais::create([
            'etablissement_id' => $frais->etablissement_id,
            'nom'              => $frais->nom,
            'description'      => $frais->description,
            'montant_total'    => $frais->montant_total,
            'nb_tranches_max'  => $frais->nb_tranches_max,
            'fractionnable'    => $frais->fractionnable,
            'annee_scolaire'   => $nouvelleAnnee,
            'actif'            => true,
        ]);

        foreach ($frais->echeanciers()->orderBy('numero_tranche')->get() as $echeance) {
            $nouvelleDate = \Carbon\Carbon::parse($echeance->date_echeance)->addYear();

            Echeancier::create([
                'categorie_frais_id' => $nouvelleCategorie->id,
                'numero_tranche'     => $echeance->numero_tranche,
                'libelle'            => $echeance->libelle,
                'montant'            => $echeance->montant,
                'date_echeance'      => $nouvelleDate,
            ]);
        }

        return response()->json([
            'message' => 'Catégorie « ' . $frais->nom . ' » dupliquée vers l\'année ' . $nouvelleAnnee . '. Pensez à l\'affecter aux apprenants.',
            'data'    => $this->formaterCategorie($nouvelleCategorie->load('echeanciers')),
        ], 201);
    }

    /**
     * Purge TOUTES les catégories de frais (+ échéanciers, affectations et
     * paiements) des années scolaires différentes de l'année active de
     * l'établissement. Ne touche jamais à l'année en cours.
     */
    public function purgerAnneesPassees(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $etablissement    = auth()->user()->etablissement;
        $anneeActive      = \App\Support\AnneeScolaire::active($etablissement);

        $categoriesAnciennes = CategoriesFrais::where('etablissement_id', $etablissementId)
            ->where('annee_scolaire', '!=', $anneeActive)
            ->get();

        if ($categoriesAnciennes->isEmpty()) {
            return response()->json([
                'message' => 'Aucune catégorie d\'année passée à purger — tout est déjà à jour.',
                'purgees' => 0,
            ]);
        }

        $count = $categoriesAnciennes->count();

        foreach ($categoriesAnciennes as $ancienneCategorie) {
            $this->supprimerCascade($ancienneCategorie);
        }

        return response()->json([
            'message' => $count . ' catégorie(s) d\'années passées supprimée(s) définitivement.',
            'purgees' => $count,
        ]);
    }

    /**
     * Supprime en cascade : paiements liés -> frais_apprenant -> échéanciers -> catégorie.
     */
    private function supprimerCascade(CategoriesFrais $frais): void
    {
        $fraisApprenantIds = $frais->fraisApprenants()->pluck('id');

        if ($fraisApprenantIds->isNotEmpty()) {
            \App\Models\Paiement::whereIn('frais_apprenant_id', $fraisApprenantIds)->delete();
        }

        $frais->fraisApprenants()->delete();
        $frais->echeanciers()->delete();
        $frais->delete();
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
     * Détail d'une catégorie de frais (avec échéanciers).
     */
    public function show(CategoriesFrais $frais): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        return response()->json([
            'data' => $this->formaterCategorie($frais->load('echeanciers')),
        ]);
    }

    /**
     * Met à jour un échéancier existant.
     */
    public function updateEcheancier(Request $request, CategoriesFrais $frais, Echeancier $echeancier): JsonResponse
    {
        $this->autoriser();
        $this->autoriserFrais($frais);

        if ($echeancier->categorie_frais_id !== $frais->id) {
            return response()->json(['message' => 'Échéance non liée à cette catégorie.'], 403);
        }

        $validated = $request->validate([
            'numero_tranche' => ['required', 'integer', 'min:1'],
            'montant'        => ['required', 'numeric', 'min:0'],
            'date_echeance'  => ['required', 'date'],
            'libelle'        => ['nullable', 'string', 'max:100'],
        ]);

        $echeancier->update([
            'numero_tranche' => $validated['numero_tranche'],
            'montant'        => $validated['montant'],
            'date_echeance'  => $validated['date_echeance'],
            'libelle'        => $validated['libelle'] ?? $echeancier->libelle,
        ]);

        return response()->json([
            'message' => 'Échéance mise à jour.',
            'data'    => [
                'id'             => $echeancier->id,
                'numero_tranche' => (int) $echeancier->numero_tranche,
                'montant'        => (float) $echeancier->montant,
                'date_echeance'  => $echeancier->date_echeance?->format('Y-m-d'),
                'libelle'        => $echeancier->libelle,
            ],
        ]);
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

    /**
     * Répartit montant_total sur nb_tranches_max échéances : crée les tranches
     * manquantes, met à jour les montants, supprime les excédentaires, purge
     * tout l'échéancier si nb = 1. (miroir du contrôleur web)
     */
    private function synchroniserEcheanciers(CategoriesFrais $frais): void
    {
        $nb = max(1, (int) $frais->nb_tranches_max);
        $montantTotal = (float) $frais->montant_total;

        $tranches = $frais->echeanciers()->orderBy('numero_tranche')->get();

        if ($nb == 1) {
            $tranches->each->delete();
            return;
        }

        $montantParTranche = round($montantTotal / $nb);

        for ($i = 1; $i <= $nb; $i++) {
            $existe = $tranches->firstWhere('numero_tranche', $i);

            if ($existe) {
                if ($existe->montant != $montantParTranche) {
                    $existe->update(['montant' => $montantParTranche]);
                }
            } else {
                Echeancier::create([
                    'categorie_frais_id' => $frais->id,
                    'numero_tranche'     => $i,
                    'libelle'            => 'Tranche ' . $i,
                    'montant'            => $montantParTranche,
                    'date_echeance'      => now()->addMonths($i)->toDateString(),
                ]);
            }
        }

        $tranches->where('numero_tranche', '>', $nb)->each->delete();
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
            abort(403, __('api.acces_etablissement'));
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
