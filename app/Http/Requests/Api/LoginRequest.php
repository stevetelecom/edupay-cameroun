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
        // Le contrat documenté envoie `identifiant` ; l'API historique
        // attendait `login`. Les deux sont acceptés pour ne pas casser
        // les versions mobiles déjà déployées.
        $login = (string) ($this->input('login') ?: $this->input('identifiant'));

        // Un email valide n'est jamais normalisé ; sinon on normalise le téléphone
        // en 9 chiffres (accepte +237, espaces, tirets en saisie).
        if ($login !== '' && ! filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $login = $this->normaliserTelephoneCm($login);
        }

        $this->merge(['login' => $login]);
    }

    public function rules(): array
    {
        return [
            // login = identifiant = email OU téléphone (9 chiffres 6XXXXXXXX)
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
