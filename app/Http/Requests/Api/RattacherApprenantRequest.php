<?php

namespace App\Http\Requests\Api;

use App\Traits\TelephoneCamerounais;
use Illuminate\Foundation\Http\FormRequest;

class RattacherApprenantRequest extends FormRequest
{
    use TelephoneCamerounais;

    public function authorize(): bool
    {
        return true;
    }

    // Rattachement par : code_etablissement + matricule (recommandé), OU code_etablissement + nom + prenom
    protected function prepareForValidation(): void
    {
        // support optionnel d'un numéro de téléphone du payeur si l'on souhaite
        // le rattachement via recherche côté établissement
        if ($this->filled('telephone')) {
            $this->merge(['telephone' => $this->normaliserTelephoneCm((string) $this->input('telephone'))]);
        }
    }

    public function rules(): array
    {
        return [
            'mode'             => ['nullable', 'in:matricule,recherche,sms'],
            'code_etablissement' => ['nullable', 'string', 'max:50'],
            'matricule'        => ['nullable', 'string', 'max:100'],
            'nom'              => ['nullable', 'string', 'max:100'],
            'prenom'           => ['nullable', 'string', 'max:100'],
            'classe'           => ['nullable', 'string', 'max:100'],
            'telephone'        => ['nullable', 'regex:/^6\d{8}$/'],
            'direction'        => ['nullable', 'in:centre,littoral,ouest,nord,adamaoua,est,sud,sud_ouest,nord_ouest,extreme_nord'],
            'lien'             => ['nullable', 'in:parent,soi-meme'],
        ];
    }

    public function messages(): array
    {
        return [
            'code_etablissement.required_without' => 'Le code ou le nom de l\'établissement est requis.',
            'matricule.required'                  => 'Le matricule est obligatoire.',
            'telephone.regex'                     => 'Numéro invalide. Format attendu : 6XXXXXXXX.',
            'mode.in'                             => 'Mode de rattachement invalide.',
        ];
    }
}
