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

        $user->apprenants()->detach($apprenant->id);

        return response()->json([
            'message' => 'Apprenant détaché avec succès.',
        ]);
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
