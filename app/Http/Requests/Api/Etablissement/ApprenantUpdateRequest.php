<?php

namespace App\Http\Requests\Api\Etablissement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprenantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $apprenant = $this->route('apprenant');

        return [
            'nom'            => ['required', 'string', 'max:100'],
            'prenom'         => ['required', 'string', 'max:100'],
            'classe'         => ['required', 'string', 'max:50'],
            'matricule'      => ['nullable', 'string', 'max:50', Rule::unique('apprenants', 'matricule')->ignore($apprenant?->id)],
            'date_naissance' => ['nullable', 'date'],
            'sexe'           => ['nullable', Rule::in(['M', 'F'])],
            'actif'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'     => 'Le nom est obligatoire.',
            'prenom.required'  => 'Le prénom est obligatoire.',
            'classe.required'  => 'La classe est obligatoire.',
            'matricule.unique' => 'Ce matricule est déjà utilisé.',
            'sexe.in'          => 'Sexe invalide (M ou F).',
        ];
    }
}
