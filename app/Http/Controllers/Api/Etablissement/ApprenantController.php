<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Etablissement\ApprenantStoreRequest;
use App\Http\Requests\Api\Etablissement\ApprenantUpdateRequest;
use App\Http\Resources\ApprenantResource;
use App\Models\Abonnement;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\FraisApprenant;
use App\Models\NotificationPayeur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprenantController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Liste paginée des apprenants de l'établissement (recherche / filtre classe / statut).
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $apprenants = Apprenant::with(['etablissement', 'frais.categorieFrais'])
            ->where('etablissement_id', $etablissementId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('nom', 'like', "%{$term}%")
                        ->orWhere('prenom', 'like', "%{$term}%")
                        ->orWhere('matricule', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('classe'), fn ($q) => $q->where('classe', $request->classe))
            ->when($request->filled('statut_paiement'), fn ($q) => $q->where('statut_paiement', $request->statut_paiement))
            ->when($request->filled('en_attente') && $request->boolean('en_attente'),
                fn ($q) => $q->where('source', 'payeur')->where('valide_par_etablissement', false))
            ->orderBy('nom')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data'    => ApprenantResource::collection($apprenants),
            'meta'    => [
                'current_page' => $apprenants->currentPage(),
                'last_page'    => $apprenants->lastPage(),
                'total'        => $apprenants->total(),
                'per_page'     => $apprenants->perPage(),
            ],
            'classes' => Apprenant::where('etablissement_id', $etablissementId)
                ->distinct()->orderBy('classe')->pluck('classe'),
        ]);
    }

    /**
     * Détail d'un apprenant (avec frais + parents).
     */
    public function show(Request $request, Apprenant $apprenant): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        $apprenant->load(['etablissement', 'frais.categorieFrais.echeanciers', 'parents']);

        return response()->json([
            'data'    => new ApprenantResource($apprenant),
            'parents' => $apprenant->parents->map(fn ($p) => [
                'id'          => $p->id,
                'nom_complet' => $p->nom_complet,
                'telephone'   => $p->telephone,
                'email'       => $p->email,
                'lien'        => $p->pivot?->lien,
            ]),
        ]);
    }

    /**
     * Valide un rattachement de payeur (source = payeur) → notifie les parents.
     */
    public function valider(Request $request, Apprenant $apprenant): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        $apprenant->update(['valide_par_etablissement' => true]);
        $nomComplet = $apprenant->prenom . ' ' . $apprenant->nom;

        foreach ($apprenant->parents()->get() as $payeur) {
            NotificationPayeur::create([
                'user_id' => $payeur->id,
                'titre'   => 'Rattachement validé',
                'message' => 'Votre demande de rattachement pour ' . $nomComplet . ' a été validée par l\'établissement. Vous pouvez désormais consulter et régler ses frais de scolarité.',
                'type'    => 'success',
            ]);
        }

        return response()->json([
            'message' => $nomComplet . ' a été validé(e). Le payeur a été notifié.',
            'data'    => new ApprenantResource($apprenant->fresh(['etablissement', 'frais.categorieFrais'])),
        ]);
    }

    /**
     * Rejette un rattachement de payeur et supprime l'apprenant créé par la famille.
     */
    public function rejeter(Request $request, Apprenant $apprenant): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        if ($apprenant->source !== 'payeur' || $apprenant->valide_par_etablissement) {
            return response()->json(['message' => 'Cet apprenant ne peut pas être rejeté.'], 403);
        }

        $nomComplet = $apprenant->prenom . ' ' . $apprenant->nom;
        $payeurs    = $apprenant->parents()->get();

        $apprenant->parents()->detach();
        $apprenant->delete();

        foreach ($payeurs as $payeur) {
            NotificationPayeur::create([
                'user_id' => $payeur->id,
                'titre'   => 'Rattachement refusé',
                'message' => 'Votre demande de rattachement pour ' . $nomComplet . ' a été refusée par l\'établissement. Vérifiez les informations saisies.',
                'type'    => 'error',
            ]);
        }

        return response()->json([
            'message' => 'La demande de rattachement de ' . $nomComplet . ' a été rejetée. Le payeur a été notifié.',
        ]);
    }

    /**
     * Crée un apprenant. Optionnellement avec une catégorie de frais (IDOR protégée).
     */
    public function store(ApprenantStoreRequest $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $validated       = $request->validated();
        $categorieFraisId = $validated['categorie_frais_id'] ?? null;

        // Limite abonnement
        $abonnement = Abonnement::where('etablissement_id', $etablissementId)
            ->whereIn('statut', ['actif', 'grace_period'])->latest()->first();
        if ($abonnement) {
            $plan   = Abonnement::PLANS[$abonnement->plan] ?? null;
            $maxApp = $plan['max_apprenants'] ?? -1;
            if ($maxApp > 0) {
                $nbActuels = Apprenant::where('etablissement_id', $etablissementId)->where('actif', true)->count();
                if ($nbActuels >= $maxApp) {
                    return response()->json([
                        'message' => 'Limite atteinte : votre plan ' . ucfirst($abonnement->plan) . ' autorise ' . $maxApp . ' apprenants actifs maximum.',
                    ], 422);
                }
            }
        }

        $validated['etablissement_id'] = $etablissementId;
        $validated['actif']            = $request->boolean('actif', true);
        $validated['statut_paiement']  = 'impaye';
        unset($validated['categorie_frais_id']);

        if (empty($validated['matricule'])) {
            $validated['matricule'] = $this->genererMatricule($etablissementId);
        }

        $apprenant = Apprenant::create($validated);

        if ($categorieFraisId) {
            $categorieFrais = CategoriesFrais::findOrFail($categorieFraisId);
            FraisApprenant::create([
                'apprenant_id'       => $apprenant->id,
                'categorie_frais_id' => $categorieFraisId,
                'montant_total'      => $categorieFrais->montant_total,
                'montant_paye'       => 0,
                'statut'             => 'impaye',
                'annee_scolaire'     => $categorieFrais->annee_scolaire ?? '2025-2026',
            ]);
        }

        return response()->json([
            'message' => 'Apprenant ' . $apprenant->nom . ' ' . $apprenant->prenom . ' ajouté avec succès.',
            'data'    => new ApprenantResource($apprenant->fresh(['etablissement', 'frais.categorieFrais'])),
        ], 201);
    }

    /**
     * Met à jour un apprenant.
     */
    public function update(ApprenantUpdateRequest $request, Apprenant $apprenant): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        $validated = $request->validated();
        $validated['actif'] = $request->boolean('actif', $apprenant->actif);

        $apprenant->update($validated);

        return response()->json([
            'message' => 'Informations mises à jour avec succès.',
            'data'    => new ApprenantResource($apprenant->fresh(['etablissement', 'frais.categorieFrais'])),
        ]);
    }

    /**
     * Supprime un apprenant.
     */
    public function destroy(Request $request, Apprenant $apprenant): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        $apprenant->parents()->detach();
        $apprenant->delete();

        return response()->json(['message' => 'Apprenant supprimé avec succès.']);
    }

    /**
     * Suppression groupée d'apprenants (miroir web bulkDestroy).
     * 🔒 Seuls les apprenants de CET établissement sont supprimés.
     *
     * Body attendu : { "ids": [1,2,3] } (requis)
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $validated = $request->validate([
            'ids'      => 'required|array',
            'ids.*'    => 'integer',
            'classe'   => 'nullable|string|max:50',
            'statut_paiement' => 'nullable|string|max:20',
            'supprimer_toutes_les_pages' => 'nullable|boolean',
        ]);

        $ids = array_unique(array_map('intval', $validated['ids']));

        $query = Apprenant::where('etablissement_id', $etablissementId)->whereIn('id', $ids);

        if (! empty($validated['supprimer_toutes_les_pages'])) {
            if (! empty($validated['classe'])) {
                $query->where('classe', $validated['classe']);
            }
            if (! empty($validated['statut_paiement'])) {
                $query->where('statut_paiement', $validated['statut_paiement']);
            }
        }

        $count = (clone $query)->count();

        $query->get()->each(function (Apprenant $apprenant) {
            $apprenant->parents()->detach();
            $apprenant->delete();
        });

        return response()->json([
            'message' => $count . ' apprenant(s) supprimé(s) avec succès.',
            'supprimés' => $count,
        ]);
    }

    /**
     * Désaffecte une catégorie de frais d'un apprenant (miroir web desaffecter).
     * 🔒 Permission identique au web : refus si des paiements sont enregistrés.
     */
    public function desaffecter(Request $request, Apprenant $apprenant, FraisApprenant $fraisApprenant): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        if ($fraisApprenant->apprenant_id !== $apprenant->id) {
            return response()->json(['message' => 'Cette affectation ne correspond pas à cet apprenant.'], 422);
        }

        if (! $fraisApprenant->categorieFrais || $fraisApprenant->categorieFrais->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Catégorie de frais non autorisée.'], 403);
        }

        if ($fraisApprenant->paiements()->exists()) {
            return response()->json([
                'message' => 'Impossible de désaffecter : des paiements sont déjà enregistrés pour cette catégorie.',
            ], 422);
        }

        $nom = $fraisApprenant->categorieFrais->nom ?? 'la catégorie';
        $fraisApprenant->delete();

        return response()->json([
            'message' => 'Catégorie « ' . $nom . ' » désaffectée avec succès.',
        ]);
    }

    /**
     * Télécharge le modèle d'import CSV (équivalent web importTemplate).
     */
    public function importTemplate(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->autoriser();

        $file = public_path('templates/apprenants_template.csv');

        if (! file_exists($file)) {
            abort(404, 'Modèle d\'import indisponible.');
        }

        return response()->download($file, 'modele_import_apprenants.csv');
    }

    /**
     * Import en masse d'apprenants depuis un fichier CSV (équivalent web import).
     */
    public function import(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $request->validate([
            'fichier_csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'fichier_csv.required' => 'Veuillez sélectionner un fichier CSV.',
            'fichier_csv.mimes'    => 'Le fichier doit être au format CSV (.csv).',
            'fichier_csv.max'      => 'Le fichier ne doit pas dépasser 2 Mo.',
        ]);

        $handle = fopen($request->file('fichier_csv')->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json(['message' => 'Impossible de lire le fichier CSV.'], 422);
        }

        // Sauter la ligne d'en-tête
        $header = fgetcsv($handle, 1000, ',');
        if ($header === false) {
            fclose($handle);
            return response()->json(['message' => 'Le fichier CSV est vide ou corrompu.'], 422);
        }

        $succes   = 0;
        $doublons = 0;
        $erreurs  = [];
        $ligne    = 1;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $ligne++;
            $row = array_map('trim', $row);

            if (count($row) < 3) {
                $erreurs[] = "Ligne $ligne : données insuffisantes (nom, prénom, classe obligatoires).";
                continue;
            }

            $nom           = $row[0] ?? null;
            $prenom        = $row[1] ?? null;
            $classe        = $row[2] ?? null;
            $matricule     = ! empty($row[3]) ? strtoupper($row[3]) : null;
            $dateNaissance = ! empty($row[4]) ? $row[4] : null;
            $sexe          = ! empty($row[5]) ? strtoupper(substr(trim($row[5]), 0, 1)) : null;

            if (empty($nom) || empty($prenom) || empty($classe)) {
                $erreurs[] = "Ligne $ligne : nom, prénom et classe sont obligatoires.";
                continue;
            }

            if ($dateNaissance) {
                $d = \DateTime::createFromFormat('Y-m-d', $dateNaissance);
                if (! $d || $d->format('Y-m-d') !== $dateNaissance) {
                    $erreurs[] = "Ligne $ligne : date_naissance invalide — format attendu AAAA-MM-JJ.";
                    continue;
                }
            }

            if ($sexe && ! in_array($sexe, ['M', 'F'])) {
                $sexe = null;
            }

            if ($matricule) {
                $search = ['etablissement_id' => $etablissementId, 'matricule' => $matricule];
            } else {
                $search = [
                    'etablissement_id' => $etablissementId,
                    'nom'              => strtoupper($nom),
                    'prenom'           => $prenom,
                    'classe'           => $classe,
                ];
            }

            if (\App\Models\Apprenant::where($search)->exists()) {
                $doublons++;
                continue;
            }

            try {
                \App\Models\Apprenant::create([
                    'etablissement_id' => $etablissementId,
                    'prenom'           => $prenom,
                    'nom'              => strtoupper($nom),
                    'classe'           => $classe,
                    'matricule'        => $matricule,
                    'date_naissance'   => $dateNaissance ?: null,
                    'sexe'             => $sexe ?: null,
                    'actif'            => true,
                    'valide_par_etablissement' => true,
                ]);
                $succes++;
            } catch (\Throwable $e) {
                $erreurs[] = "Ligne $ligne : erreur lors de l'insertion.";
            }
        }

        fclose($handle);

        return response()->json([
            'message'      => "$succes apprenant(s) importé(s) avec succès" . ($doublons ? ", $doublons doublon(s) ignoré(s)" : '') . ($erreurs ? ', ' . count($erreurs) . ' ligne(s) en erreur.' : '.'),
            'importes'     => $succes,
            'doublons'     => $doublons,
            'erreurs'      => $erreurs,
        ]);
    }

    private function genererMatricule(int $etablissementId): string
    {
        $etablissement = auth()->user()->etablissement;

        // Base = code complet de l'établissement (ex. LYC-MEL-2026) ou initiales du nom
        $base = $etablissement->code_etablissement
            ? strtoupper(trim($etablissement->code_etablissement))
            : strtoupper(substr(preg_replace('/[^A-Z]/i', '', $etablissement->nom), 0, 3));

        $dernier = Apprenant::withTrashed()->where('etablissement_id', $etablissementId)
            ->whereNotNull('matricule')->orderByDesc('id')->value('matricule');

        $numero = 1;
        if ($dernier && preg_match('/(\d+)$/', $dernier, $m)) {
            $numero = (int) $m[1] + 1;
        }

        // Boucle de retry : garantit l'unicité (contrainte UNIQUE en base)
        do {
            $matricule = $base . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
            $libre = ! Apprenant::withTrashed()->where('matricule', $matricule)->exists();
            $numero++;
        } while (! $libre);

        return $matricule;
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
