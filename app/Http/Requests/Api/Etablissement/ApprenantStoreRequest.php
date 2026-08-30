<?php

namespace App\Http\Requests\Api\Etablissement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprenantStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->etablissement_id;

        return [
            'nom'               => ['required', 'string', 'max:100'],
            'prenom'            => ['required', 'string', 'max:100'],
            'classe'            => ['required', 'string', 'max:50'],
            'matricule'         => ['nullable', 'string', 'max:50', 'unique:apprenants,matricule'],
            'date_naissance'    => ['nullable', 'date'],
            'sexe'              => ['nullable', Rule::in(['M', 'F'])],
            'actif'             => ['nullable', 'boolean'],
            // 🔒 IDOR : la catégorie de frais doit appartenir à CET établissement
            'categorie_frais_id' => [
                'nullable',
                Rule::exists('categories_frais', 'id')->where('etablissement_id', $etablissementId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'       => 'Le nom est obligatoire.',
            'prenom.required'    => 'Le prénom est obligatoire.',
            'classe.required'    => 'La classe est obligatoire.',
            'matricule.unique'   => 'Ce matricule est déjà utilisé.',
            'sexe.in'            => 'Sexe invalide (M ou F).',
            'categorie_frais_id.exists' => 'Catégorie de frais invalide pour cet établissement.',
        ];
    }
}
