<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RattacherApprenantRequest;
use App\Http\Resources\ApprenantResource;
use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprenantController extends Controller
{
    /**
     * Liste des apprenants rattachés à l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $apprenants = $request->user()
            ->apprenants()
            ->with(['etablissement', 'frais.categorieFrais.echeanciers'])
            ->get();

        return response()->json([
            'data' => ApprenantResource::collection($apprenants),
        ]);
    }

    /**
     * Rattache un apprenant (par code_établissement + matricule, ou recherche par nom).
     * Crée un rattachement en attente de validation par l'établissement si non trouvé.
     */
    public function rattacher(RattacherApprenantRequest $request): JsonResponse
    {
        $valid = $request->validated();
        $user  = $request->user();

        // T : un seul point de resolution de l'etablissement, accepte au
        // contrat (`etablissement_id`) comme au formulaire mobile
        // (`code_etablissement`). Les deux branches du rattachement sont
        // bornees a cet etablissement.
        $etablissement = $this->resoudreEtablissement($valid);

        if ($etablissement->statut !== 'actif') {
            return response()->json([
                'message' => 'Cet établissement n\'accepte pas de nouveau rattachement pour le moment.',
                'errors'  => ['etablissement_id' => ['Établissement non actif.']],
            ], 422);
        }

        // Identification exacte : matricule.
        if (! empty($valid['matricule'])) {
            $apprenant = $etablissement->apprenants()
                ->where('matricule', $valid['matricule'])
                ->first();

            if (! $apprenant) {
                return response()->json([
                    'message' => 'Aucun apprenant ne correspond à ce matricule dans cet établissement.',
                    'errors'  => ['matricule' => ['Matricule inconnu.']],
                ], 422);
            }

            return $this->rattacherEtRetourner($user, $apprenant, $valid);
        }

        // Recherche dans CET établissement uniquement (jamais de recherche
        // plateforme : elle exposait les eleves des autres ecoles).
        $apprenants = Apprenant::where('etablissement_id', $etablissement->id)
            ->where('nom', 'like', '%' . $valid['nom'] . '%')
            ->when($valid['prenom'] ?? null, fn ($q, $prenom) => $q->where('prenom', 'like', '%' . $prenom . '%'))
            ->when($valid['classe'] ?? null, fn ($q, $classe) => $q->where('classe', 'like', '%' . $classe . '%'))
            ->limit(20)
            ->get();

        if ($apprenants->isEmpty()) {
            return response()->json([
                'message' => 'Aucun apprenant trouvé. Vérifiez les informations saisies.',
                'data'    => [],
            ], 200);
        }

        if ($apprenants->count() === 1) {
            return $this->rattacherEtRetourner($user, $apprenants->first(), $valid);
        }

        return response()->json([
            'message' => 'Plusieurs apprenants correspondent. Sélectionnez-en un.',
            'data'    => ApprenantResource::collection($apprenants),
        ]);
    }

    /**
     * Resout l'etablissement cible du rattachement, depuis l'identifiant ou
     * le code. Les deux parametres sont deja valides par la FormRequest.
     */
    private function resoudreEtablissement(array $valid): Etablissement
    {
        if (! empty($valid['etablissement_id'])) {
            return Etablissement::findOrFail($valid['etablissement_id']);
        }

        return Etablissement::where('code_etablissement', $valid['code_etablissement'])->firstOrFail();
    }

/**
     * Détache un apprenant de l'utilisateur connecté.
     */
    public function detacher(Request $request, Apprenant $apprenant): JsonResponse
    {
        $user = $request->user();

        $estRattache = $user->apprenants()->where('apprenants.id', $apprenant->id)->exists();

        if (! $estRattache) {
            return response()->json(['message' => 'Cet apprenant ne vous est pas rattaché.'], 403);
        }

        // Permission : impossible de détacher un enfant à qui des frais de
        // scolarité ont déjà été affectés (même sans paiement) — cohérent web/API.
        if ($apprenant->frais()->exists() || $apprenant->paiements()->exists()) {
            return response()->json([
                'message' => 'Impossible de retirer ' . $apprenant->prenom
                    . ' : des frais de scolarité ou des paiements sont déjà enregistrés pour cet apprenant. '
                    . 'Veuillez le détacher avant la prochaine affectation de frais.',
            ], 422);
        }

        $user->apprenants()->detach($apprenant->id);

        return response()->json([
            'message' => 'Apprenant détaché avec succès.',
        ]);
    }

    /**
     * Le parent met à jour les informations de l'enfant rattaché
     * (classe, prénom, nom, établissement par nom). Miroir de l'onboarding web.
     */
    public function updateInfo(Request $request, Apprenant $apprenant): JsonResponse
    {
        $user = $request->user();

        $estRattache = $user->apprenants()->where('apprenants.id', $apprenant->id)->exists();
        if (! $estRattache) {
            return response()->json(['message' => 'Cet apprenant ne vous est pas rattaché.'], 403);
        }

        $validated = $request->validate([
            'etablissement_id'  => ['nullable', 'exists:etablissements,id'],
            'etablissement_nom' => ['required_without:etablissement_id', 'nullable', 'string', 'max:150'],
            'classe'            => ['required', 'string', 'max:50'],
            'matricule'         => ['nullable', 'string', 'max:50'],
            'prenom'            => ['required', 'string', 'max:100'],
            'nom'               => ['required', 'string', 'max:100'],
        ]);

        foreach (['prenom', 'nom', 'classe', 'etablissement_nom', 'matricule'] as $champ) {
            if (! empty($validated[$champ])) {
                $validated[$champ] = strip_tags(trim($validated[$champ]));
            }
        }

        if (! empty($validated['etablissement_id'])) {
            $etablissement = Etablissement::find($validated['etablissement_id']);
        } else {
            $etablissement = Etablissement::where('nom', 'like', '%' . $validated['etablissement_nom'] . '%')
                ->where('statut', 'actif')
                ->first();
        }

        if (! $etablissement) {
            return response()->json([
                'message' => 'Établissement introuvable.',
                'errors'  => ['etablissement_nom' => ['Établissement introuvable.']],
            ], 422);
        }

        if ($apprenant->paiements()->exists() && $apprenant->etablissement_id !== $etablissement->id) {
            return response()->json([
                'message' => 'Impossible de changer l\'établissement : des paiements sont déjà enregistrés pour cet apprenant.',
            ], 422);
        }

        $apprenant->update([
            'etablissement_id' => $etablissement->id,
            'prenom'           => $validated['prenom'],
            'nom'              => $validated['nom'],
            'classe'           => $validated['classe'],
            'matricule'        => $validated['matricule'] ?? $apprenant->matricule,
        ]);

        return response()->json([
            'message' => 'Informations de ' . $apprenant->prenom . ' mises à jour.',
            'data'    => new ApprenantResource($apprenant->fresh(['etablissement', 'frais.categorieFrais.echeanciers'])),
        ]);
    }

    /**
     * Liste enrichie "Mes enfants" (parents) / "Mon dossier" (élève, premier apprenant) :
     * infos établissement, total du/payé, premier impayé. Miroir du web.
     */
    public function mesEnfants(Request $request): JsonResponse
    {
        $user = $request->user();

        $apprenants = $user->apprenants()
            ->with(['frais.categorieFrais', 'etablissement'])
            ->get();

        $premierFraisImpaye = null;
        foreach ($apprenants as $apprenant) {
            $fraisImpaye = $apprenant->frais->first(fn ($f) => $f->statut !== 'regle');
            if ($fraisImpaye) {
                $premierFraisImpaye = $fraisImpaye;
                break;
            }
        }

        $monDossier = null;
        if (in_array($user->profil ?? '', ['eleve', 'etudiant'])) {
            $monDossier = $apprenants->first();
        }

        return response()->json([
            'data' => [
                'apprenants'          => $apprenants->map(fn ($a) => [
                    'id'                 => $a->id,
                    'nom'                => $a->nom,
                    'prenom'             => $a->prenom,
                    'matricule'          => $a->matricule,
                    'classe'             => $a->classe,
                    'statut_paiement'    => $a->statut_paiement,
                    'valide_par_etablissement' => (bool) $a->valide_par_etablissement,
                    'etablissement'      => [
                        'id'   => $a->etablissement?->id,
                        'nom'  => $a->etablissement?->nom,
                        'ville'=> $a->etablissement?->ville,
                        'logo' => $a->etablissement?->logo ? asset('storage/' . $a->etablissement->logo) : null,
                    ],
                    'total_du'           => $a->frais->sum(fn ($f) => $f->montant_total - $f->montant_paye),
                    'total_paye'         => $a->frais->sum('montant_paye'),
                    'premier_frais_impaye' => $this->fraisImpayeApercu($a->frais->first(fn ($f) => $f->statut !== 'regle')),
                ]),
                'premier_frais_impaye' => $this->fraisImpayeApercu($premierFraisImpaye),
                'mon_dossier'          => $monDossier ? $this->fraisImpayeApercu($monDossier->frais->first(fn ($f) => $f->statut !== 'regle')) : null,
            ],
        ]);
    }

    /**
     * Liste des établissements actifs (pour sélection lors d'un rattachement).
     */
    public function etablissements(Request $request): JsonResponse
    {
        $etablissements = Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'type', 'code_etablissement', 'logo']);

        return response()->json([
            'data' => $etablissements->map(fn ($e) => [
                'id'                 => $e->id,
                'nom'                => $e->nom,
                'ville'              => $e->ville,
                'type'               => $e->type,
                'code_etablissement' => $e->code_etablissement,
                'logo'               => $e->logo ? asset('storage/' . $e->logo) : null,
            ]),
        ]);
    }

    /**
     * Recherche d'apprenants dans un établissement donné (annuaire de
     * rattachement). Miroir de OnboardingController::searchApprenants (web).
     *
     * Sécurité (E-01) : pas de recherche libre exposant tout l'annuaire dès
     * la première frappe. 3 caractères minimum requis, sauf pour lister
     * l'annuaire complet quand aucune recherche n'est saisie.
     */
    public function searchApprenants(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'etablissement_id' => ['sometimes', 'required', 'integer', 'exists:etablissements,id'],
            // Borne haute : sans elle, `q` arrivait arbitrairement long et
            // chaque mot de la saisie devenait un `LIKE %mot%` sur toute la
            // table des apprenants.
            'q' => ['sometimes', 'nullable', 'string', 'max:60'],
        ]);

        $etablissementId = $validated['etablissement_id'] ?? null;
        $search          = trim((string) ($validated['q'] ?? ''));

        if (! $etablissementId) {
            return response()->json(['data' => []]);
        }

        $query = Apprenant::where('etablissement_id', $etablissementId)
            ->where('actif', true);

        if ($search !== '') {
            if (mb_strlen($search) < 3) {
                return response()->json(['data' => []]);
            }

            $query->where(function ($query) use ($search) {
                $query->where('matricule', $search)
                    ->orWhere(function ($sub) use ($search) {
                        $mots = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($mots as $mot) {
                            $sub->where(function ($s) use ($mot) {
                                $s->where('nom', 'like', "%{$mot}%")
                                  ->orWhere('prenom', 'like', "%{$mot}%");
                            });
                        }
                    });
            });
        }

        $apprenants = $query
            ->orderBy('nom')
            ->limit(10)
            ->get(['id', 'nom', 'prenom', 'classe', 'matricule']);

        return response()->json([
            'data' => $apprenants->map(fn ($a) => [
                'id'        => $a->id,
                'nom'       => $a->nom,
                'prenom'    => $a->prenom,
                'classe'    => $a->classe,
                'matricule' => $a->matricule,
            ]),
        ]);
    }

    private function fraisImpayeApercu($frais): ?array
    {
        if (! $frais) {
            return null;
        }

        return [
            'id'             => $frais->id,
            // O : `categorieFrais` est la cle canonique attendue par le
            // mobile (le nom seul ne permet ni le tri ni le filtre).
            'categorieFrais' => $frais->categorieFrais ? [
                'id'            => $frais->categorieFrais->id,
                'nom'           => $frais->categorieFrais->nom,
                'annee_scolaire' => $frais->categorieFrais->annee_scolaire,
            ] : null,
            'categorie'      => $frais->categorieFrais?->nom,
            'montant_total'  => (float) $frais->montant_total,
            'montant_paye'   => (float) $frais->montant_paye,
            'reste'          => (float) ($frais->montant_total - $frais->montant_paye),
            'statut'         => $frais->statut,
            'annee_scolaire' => $frais->annee_scolaire,
        ];
    }

    private function rattacherEtRetourner(User $user, Apprenant $apprenant, array $valid): JsonResponse
    {
        $dejaRattache = $user->apprenants()->where('apprenants.id', $apprenant->id)->exists();

        if ($dejaRattache) {
            return response()->json([
                'message' => 'Cet apprenant est déjà rattaché à votre compte.',
                'data'    => new ApprenantResource($apprenant->load(['etablissement', 'frais.categorieFrais.echeanciers'])),
            ]);
        }

        DB::transaction(function () use ($user, $apprenant, $valid) {
            $user->apprenants()->attach($apprenant->id, [
                'lien' => $valid['lien'] ?? 'parent',
            ]);
        });

        $apprenant->load(['etablissement', 'frais.categorieFrais.echeanciers']);

        return response()->json([
            'success' => true,
            'message' => 'Apprenant rattaché avec succès. Si l\'établissement exige une validation, vous en serez notifié.',
            // T : le contrat documente lit `apprenant_id`, `nom_complet` et
            // `etablissement` (nom de l'ecole). On les expose explicitement,
            // la ressource complete reste disponible pour le reste.
            'data'    => array_merge(
                (new ApprenantResource($apprenant))->resolve(request()),
                [
                    'apprenant_id'    => $apprenant->id,
                    'nom_complet'     => trim($apprenant->prenom . ' ' . $apprenant->nom),
                    'etablissement'    => $apprenant->etablissement?->nom,
                    'classe'          => $apprenant->classe,
                    'statut_paiement' => $apprenant->statut_paiement,
                ]
            ),
        ], 201);
    }
}
