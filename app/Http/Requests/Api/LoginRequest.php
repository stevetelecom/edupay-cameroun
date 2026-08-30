<?php

namespace App\Http\Requests\Api;

use App\Traits\TelephoneCamerounais;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use TelephoneCamerounais;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $login = (string) $this->input('login');
        // Un email valide n'est jamais normalisé ; sinon on normalise le téléphone
        // en 9 chiffres (accepte +237, espaces, tirets en saisie).
        if ($this->filled('login') && ! filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $this->merge(['login' => $this->normaliserTelephoneCm($login)]);
        }
    }

    public function rules(): array
    {
        return [
            // login = email OU téléphone (9 chiffres 6XXXXXXXX)
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => 'L\'email ou le téléphone est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ];
    }
}
