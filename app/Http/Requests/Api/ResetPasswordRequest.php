<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'exists:users,email'],
            'code'     => ['required', 'string', 'digits:6'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'      => 'L\'adresse email est obligatoire.',
            'email.exists'        => 'Aucun compte ne correspond à cet email.',
            'code.required'       => 'Le code de réinitialisation est obligatoire.',
            'code.digits'         => 'Le code doit comporter 6 chiffres.',
            'password.required'   => 'Le mot de passe est obligatoire.',
            'password.min'        => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'  => 'Les mots de passe ne correspondent pas.',
            'password.regex'      => 'Le mot de passe doit contenir 1 majuscule, 1 chiffre et 1 caractère spécial.',
        ];
    }
}
