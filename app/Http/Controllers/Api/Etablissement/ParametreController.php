<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\CategoriesFrais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ParametreController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Paramètres de l'établissement (infos + nombre de catégories de frais).
     */
    public function index(): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $etablissement   = auth()->user()->etablissement;

        $categoriesFrais = CategoriesFrais::where('etablissement_id', $etablissementId)
            ->orderBy('nom')->get();

        return response()->json([
            'data' => [
                'etablissement' => $this->formaterEtablissement($etablissement),
                'nb_categories_frais' => $categoriesFrais->count(),
            ],
        ]);
    }

    /**
     * Met à jour les paramètres de l'établissement (infos + logo + agrément).
     */
    public function update(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $etablissement   = auth()->user()->etablissement;

        $normaliserTelephoneCm = function (string $value): string {
            $digits = preg_replace('/\D/', '', $value);
            return strlen($digits) > 9 ? substr($digits, -9) : $digits;
        };

        $request->merge([
            'telephone'               => $normaliserTelephoneCm((string) $request->input('telephone', '')),
            'numero_momo_reversement' => $normaliserTelephoneCm((string) $request->input('numero_momo_reversement', '')),
        ]);

        $validated = $request->validate([
            'nom'                    => ['required', 'string', 'max:150'],
            'type'                   => ['required', Rule::in(['maternelle','primaire','secondaire','universitaire','formation'])],
            'statut_juridique'       => ['nullable', 'string', 'max:100'],
            'numero_agrement'        => ['nullable', 'string', 'max:100'],
            'nb_eleves'              => ['nullable', 'in:moins_100,100_300,300_500,500_1000,plus_1000'],
            'region'                 => ['nullable', 'string', 'max:100'],
            'ville'                  => ['required', 'string', 'max:100'],
            'quartier'               => ['nullable', 'string', 'max:100'],
            'boite_postale'          => ['nullable', 'string', 'max:50'],
            'telephone'              => ['required', 'regex:/^[236]\d{8}$/'],
            'email'                  => ['required', 'email', 'max:150'],
            'site_web'               => ['nullable', 'url', 'max:200'],
            'description'            => ['nullable', 'string', 'max:1000'],
            'mobile_money_principal' => ['required', Rule::in(['mtn', 'orange'])],
            'numero_momo_reversement'=> ['required', 'regex:/^6\d{8}$/'],
            'logo'                   => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'document_agrement'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'telephone.regex'               => 'Numéro invalide. Format attendu : 6XXXXXXXX (mobile) ou 2XXXXXXXX / 3XXXXXXXX (fixe/Camtel).',
            'numero_momo_reversement.regex' => 'Numéro Mobile Money invalide. Format attendu : 6XXXXXXXX.',
        ]);

        if ($request->hasFile('logo')) {
            if ($etablissement->logo) {
                Storage::disk('public')->delete($etablissement->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        } else {
            unset($validated['logo']);
        }

        if ($request->hasFile('document_agrement')) {
            if ($etablissement->document_agrement) {
                Storage::disk('public')->delete($etablissement->document_agrement);
            }
            $validated['document_agrement'] = $request->file('document_agrement')->store('agrements', 'public');
        } else {
            unset($validated['document_agrement']);
        }

        $etablissement->update($validated);

        return response()->json([
            'message' => 'Paramètres mis à jour avec succès.',
            'data'    => $this->formaterEtablissement($etablissement->fresh()),
        ]);
    }

    private function formaterEtablissement($etablissement): array
    {
        return [
            'id'                       => $etablissement->id,
            'nom'                      => $etablissement->nom,
            'type'                     => $etablissement->type,
            'statut_juridique'         => $etablissement->statut_juridique,
            'numero_agrement'          => $etablissement->numero_agrement,
            'nb_eleves'                => $etablissement->nb_eleves,
            'region'                   => $etablissement->region,
            'ville'                    => $etablissement->ville,
            'quartier'                 => $etablissement->quartier,
            'boite_postale'            => $etablissement->boite_postale,
            'telephone'                => $etablissement->telephone,
            'email'                    => $etablissement->email,
            'site_web'                 => $etablissement->site_web,
            'description'              => $etablissement->description,
            'code_etablissement'       => $etablissement->code_etablissement,
            'mobile_money_principal'   => $etablissement->mobile_money_principal,
            'numero_momo_reversement'  => $etablissement->numero_momo_reversement,
            'logo'                     => $etablissement->logo ? asset('storage/' . $etablissement->logo) : null,
            'document_agrement'        => $etablissement->document_agrement ? asset('storage/' . $etablissement->document_agrement) : null,
            'statut'                   => $etablissement->statut,
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
}
