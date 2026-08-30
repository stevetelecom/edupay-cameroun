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
        $valid   = $request->validated();
        $user    = $request->user();
        $mode    = $valid['mode'] ?? 'matricule';

        if (! empty($valid['code_etablissement']) && ! empty($valid['matricule'])) {
            $etablissement = Etablissement::where('code_etablissement', $valid['code_etablissement'])->first();

            if (! $etablissement) {
                return response()->json([
                    'message' => 'Établissement introuvable pour ce code.',
                    'errors'  => ['code_etablissement' => ['Code d\'établissement inconnu.']],
                ], 422);
            }

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

        // Mode "recherche" : nom + prénom + (option élève/étudiant)
        $query = Apprenant::query();

        if (! empty($valid['code_etablissement'])) {
            $etablissement = Etablissement::where('code_etablissement', $valid['code_etablissement'])->first();
            if (! $etablissement) {
                return response()->json([
                    'message' => 'Établissement introuvable pour ce code.',
                    'errors'  => ['code_etablissement' => ['Code d\'établissement inconnu.']],
                ], 422);
            }
            $query->where('etablissement_id', $etablissement->id);
        }

        if (! empty($valid['nom'])) {
            $query->where('nom', 'like', '%' . $valid['nom'] . '%');
        }
        if (! empty($valid['prenom'])) {
            $query->where('prenom', 'like', '%' . $valid['prenom'] . '%');
        }
        if (! empty($valid['classe'])) {
            $query->where('classe', 'like', '%' . $valid['classe'] . '%');
        }

        $apprenants = $query->limit(20)->get();

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
     * Détache un apprenant de l'utilisateur connecté.
     */
    public function detacher(Request $request, Apprenant $apprenant): JsonResponse
    {
        $user = $request->user();

        $estRattache = $user->apprenants()->where('apprenants.id', $apprenant->id)->exists();

        if (! $estRattache) {
            return response()->json(['message' => 'Cet apprenant ne vous est pas rattaché.'], 403);
        }

        if ($apprenant->paiements()->exists()) {
            return response()->json([
                'message' => 'Impossible de retirer ' . $apprenant->prenom
                    . ' : des paiements sont enregistrés. Contactez votre établissement.',
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

    private function fraisImpayeApercu($frais): ?array
    {
        if (! $frais) {
            return null;
        }

        return [
            'id'             => $frais->id,
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

        return response()->json([
            'message' => 'Apprenant rattaché avec succès. Si l\'établissement exige une validation, vous en serez notifié.',
            'data'    => new ApprenantResource($apprenant->load(['etablissement', 'frais.categorieFrais.echeanciers'])),
        ], 201);
    }
}
