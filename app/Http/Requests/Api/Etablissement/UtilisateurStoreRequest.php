<?php

namespace App\Http\Requests\Api\Etablissement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UtilisateurStoreRequest extends FormRequest
{
    private const ROLES_INTERNES = ['directeur', 'comptable', 'caissier'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenom'    => ['required', 'string', 'max:100'],
            'nom'       => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:150', 'unique:users,email'],
            'telephone' => ['required', 'string', 'max:20', 'unique:users,telephone'],
            'role'      => ['required', Rule::in(self::ROLES_INTERNES)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'Cette adresse email est déjà utilisée.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'role.in'          => 'Rôle invalide (directeur, comptable ou caissier).',
        ];
    }
}
