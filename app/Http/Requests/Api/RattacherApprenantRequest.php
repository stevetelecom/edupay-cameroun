<?php

namespace App\Http\Requests\Api;

use App\Traits\TelephoneCamerounais;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rattachement d'un apprenant par un payeur.
 *
 * Deux voies :
 *  - identification exacte : etablissement + matricule ;
 *  - recherche : etablissement + nom (prenom / classe affinés).
 *
 * T (sécurité) : avant, `code_etablissement`, `matricule`, `nom` et `prenom`
 * étaient tous `nullable`. Une requête vide passait la validation et
 * atteignait `Apprenant::query()` sans aucun filtre : l'API renvoyait les
 * 20 premiers apprenants de TOUTE la plateforme (nom, prénom, classe,
 * matricule, école) a n'importe quel compte authentifie.
 *
 * T (contrat) : `etablissement_id` fait partie du contrat documente
 * (docs/DOCUMENTATION_API.md) mais n'etait ni valide ni lu. Il est desormais
 * accepte, au meme titre que `code_etablissement`, et l'un des deux est
 * obligatoire.
 */
class RattacherApprenantRequest extends FormRequest
{
    use TelephoneCamerounais;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise le numero de telephone avant validation (rattachement par
     * recherche sur le contact du payeur).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('telephone')) {
            $this->merge(['telephone' => $this->normaliserTelephoneCm((string) $this->input('telephone'))]);
        }
    }

    public function rules(): array
    {
        return [
            'mode'              => ['nullable', 'in:matricule,recherche,sms'],
            'lien'              => ['nullable', 'in:parent,soi-meme'],

            // L'etablissement cible est obligatoire : sans lui, la recherche
            // ne peut pas etre bornee etBalaye les autres ecoles.
            'etablissement_id'  => [
                'required_without:code_etablissement',
                'nullable',
                'integer',
                Rule::exists('etablissements', 'id'),
            ],
            'code_etablissement' => [
                'required_without:etablissement_id',
                'nullable',
                'string',
                'max:50',
                Rule::exists('etablissements', 'code_etablissement'),
            ],

            // Au moins un critere d'identification, sinon la requete ne
            // selectionne rien et l'API listerait des eleves strangers.
            'matricule'         => ['required_without:nom', 'nullable', 'string', 'max:100'],
            'nom'               => ['required_without:matricule', 'nullable', 'string', 'max:100'],
            'prenom'            => ['nullable', 'string', 'max:100'],
            'classe'            => ['nullable', 'string', 'max:100'],
            'telephone'         => ['nullable', 'regex:/^6\d{8}$/'],
            'direction'         => ['nullable', 'in:centre,littoral,ouest,nord,adamaoua,est,sud,sud_ouest,nord_ouest,extreme_nord'],
        ];
    }

    public function messages(): array
    {
        return [
            'etablissement_id.required_without'  => 'Sélectionnez l\'établissement de votre enfant.',
            'code_etablissement.required_without' => 'Renseignez le code de l\'établissement de votre enfant.',
            'code_etablissement.exists'          => 'Établissement introuvable pour ce code.',
            'etablissement_id.exists'            => 'Établissement introuvable.',
            'matricule.required_without'         => 'Saisissez le matricule ou le nom de votre enfant.',
            'nom.required_without'               => 'Saisissez le nom ou le matricule de votre enfant.',
            'telephone.regex'                    => 'Numéro invalide. Format attendu : 6XXXXXXXX.',
            'mode.in'                            => 'Mode de rattachement invalide.',
        ];
    }
}
