<?php

namespace App\Http\Requests\Api\Etablissement;

use App\Traits\TelephoneCamerounais;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UtilisateurStoreRequest extends FormRequest
{
    use TelephoneCamerounais;

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
            // Optionnel : le formulaire web ne le propose pas (audit B).
            'telephone' => ['nullable', 'string', 'max:20', 'unique:users,telephone'],
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

    /**
     * Normalise le téléphone AVANT la validation : `unique` compare alors la
     * valeur stockée en base (9 chiffres) et non la saisie brute, ce qui
     * empêche un doublon caché derrière un autre format (« +237 6 99 … »).
     */
    protected function prepareForValidation(): void
    {
        $telephone = $this->normaliserTelephoneCm((string) $this->input('telephone'));

        $this->merge(['telephone' => $telephone === '' ? null : $telephone]);
    }
}
