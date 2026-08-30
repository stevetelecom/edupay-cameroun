<?php

namespace App\Http\Requests\Api;

use App\Traits\TelephoneCamerounais;
use Illuminate\Foundation\Http\FormRequest;

class InitierPaiementRequest extends FormRequest
{
    use TelephoneCamerounais;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('telephone')) {
            $this->merge(['telephone' => $this->normaliserTelephoneCm((string) $this->input('telephone'))]);
        }
    }

    public function rules(): array
    {
        return [
            // Soit un frais_apprenant (paiement intégral ou tranche), soit un paiement direct
            'frais_apprenant_id' => ['nullable', 'integer', 'exists:frais_apprenant,id'],
            'apprenant_id'       => ['nullable', 'integer', 'exists:apprenants,id'],
            'echeancier_id'      => ['nullable', 'integer', 'exists:echeanciers,id'],
            'montant'            => ['required_if:frais_apprenant_id,', 'nullable', 'integer', 'min:50'],
            'type_paiement'      => ['nullable', 'in:integral,tranche'],
            'telephone'          => ['required', 'regex:/^6\d{8}$/'],
            'mode_paiement'      => ['required', 'in:mtn_momo,orange_money,carte'],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required'     => 'Le numéro de paiement est obligatoire.',
            'telephone.regex'        => 'Numéro invalide. Format attendu : 6XXXXXXXX.',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'mode_paiement.in'       => 'Mode de paiement invalide.',
            'frais_apprenant_id.exists' => 'Le frais sélectionné n\'existe pas.',
        ];
    }
}
