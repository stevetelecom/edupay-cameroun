<?php

namespace App\Http\Requests\Api\Etablissement;

use Illuminate\Foundation\Http\FormRequest;

class FraisStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'                    => ['required', 'string', 'max:150'],
            'montant_total'          => ['required', 'numeric', 'min:0'],
            'nb_tranches_max'        => ['required', 'integer', 'min:1', 'max:3'],
            'fractionnable'          => ['nullable', 'boolean'],
            'description'            => ['nullable', 'string', 'max:500'],
            'annee_scolaire'         => ['required', 'string', 'max:20'],
            'echeances'              => ['sometimes', 'array'],
            'echeances.*.date_echeance' => ['required_with:echeances', 'date'],
            'echeances.*.montant'    => ['required_with:echeances', 'numeric', 'min:0'],
            'echeances.*.libelle'    => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'             => 'Le nom est obligatoire.',
            'montant_total.required'   => 'Le montant est obligatoire.',
            'montant_total.min'        => 'Le montant doit être positif.',
            'nb_tranches_max.required' => 'Le nombre de tranches est obligatoire.',
            'nb_tranches_max.max'      => 'Maximum 3 tranches.',
            'annee_scolaire.required'  => 'L\'année scolaire est obligatoire.',
        ];
    }
}
