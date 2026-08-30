<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProfilUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenom'                => ['nullable', 'string', 'max:100'],
            'nom'                   => ['nullable', 'string', 'max:100'],
            'ville'                 => ['nullable', 'string', 'max:100'],
            'quartier'              => ['nullable', 'string', 'max:100'],
            'email'                 => ['nullable', 'email', 'max:150', 'unique:users,email,' . $this->user()->id],
            'notif_sms'             => ['nullable', 'boolean'],
            'notif_email'           => ['nullable', 'boolean'],
            'notif_rappel_echeance' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'  => 'Cette adresse email est déjà utilisée.',
            'prenom.max'    => 'Le prénom est trop long.',
            'nom.max'       => 'Le nom est trop long.',
        ];
    }
}
