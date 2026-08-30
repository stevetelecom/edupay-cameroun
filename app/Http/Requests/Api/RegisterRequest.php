<?php

namespace App\Http\Requests\Api;

use App\Traits\TelephoneCamerounais;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use TelephoneCamerounais;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('telephone')) {
            $this->merge([
                'telephone' => $this->normaliserTelephoneCm((string) $this->input('telephone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'profil'       => ['required', 'in:parent,eleve,etudiant'],
            'prenom'       => ['required', 'string', 'max:100'],
            'nom'          => ['required', 'string', 'max:100'],
            'telephone'    => ['required', 'regex:/^6\d{8}$/', 'unique:users,telephone'],
            'email'        => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'ville'        => ['required', 'string', 'max:100'],
            'quartier'     => ['nullable', 'string', 'max:100'],
            'password'     => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
            ],
            'cgu_accepted' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'profil.required'        => 'Le profil est obligatoire.',
            'profil.in'              => 'Profil invalide.',
            'prenom.required'        => 'Le prénom est obligatoire.',
            'nom.required'           => 'Le nom est obligatoire.',
            'telephone.required'     => 'Le numéro de téléphone est obligatoire.',
            'telephone.unique'       => 'Ce numéro est déjà utilisé.',
            'telephone.regex'        => 'Numéro invalide. Format attendu : 6XXXXXXXX (9 chiffres, mobile camerounais).',
            'ville.required'         => 'La ville est obligatoire.',
            'email.unique'           => 'Cette adresse email est déjà utilisée.',
            'password.min'           => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'     => 'Les mots de passe ne correspondent pas.',
            'password.regex'         => 'Le mot de passe doit contenir 1 majuscule, 1 chiffre et 1 caractère spécial.',
            'cgu_accepted.accepted'  => 'Vous devez accepter les conditions d\'utilisation.',
        ];
    }
}
