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
            // Audit D : toute somme debitee doit etre rattachee a une ligne de
            // frais. `paiements.frais_apprenant_id` est NOT NULL en base : le
            // « paiement direct » renvoyait une erreur 500 en production et
            // aurait casse les rapports financiers (pas de categorie, pas
            // d'annee scolaire). Le CDC ne prevoit pas de paiement libre.
            'frais_apprenant_id' => ['required', 'integer', 'exists:frais_apprenant,id'],
            'apprenant_id'       => ['nullable', 'integer', 'exists:apprenants,id'],
            'echeancier_id'      => ['nullable', 'integer', 'exists:echeanciers,id'],
            // Le montant est informatif : il ne determine jamais le debit
            // (voir App\Support\MontantPaiement). Le vrai garde-fou est cote
            // serveur sur le montant calcule, non sur une valeur client.
            'montant'            => ['nullable', 'integer'],
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
            'frais_apprenant_id.required' => 'Veuillez choisir les frais a regler.',
            'frais_apprenant_id.exists'   => 'Le frais selectionne est introuvable.',
            'echeancier_id.exists'        => 'L\'echeance demandee est introuvable.',
        ];
    }
}
