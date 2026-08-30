<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaiementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'reference'             => $this->reference,
            'montant'               => (int) $this->montant,
            'frais_service'         => (int) $this->frais_service,
            'montant_total_paye'    => (int) $this->montant_total_paye,
            'mode_paiement'         => $this->mode_paiement,
            'type_paiement'         => $this->type_paiement,
            'numero_tranche'        => $this->numero_tranche,
            'statut'                => $this->statut,
            'telephone_paiement'    => $this->telephone_paiement,
            'operateur'             => $this->operateur,
            'date_paiement'         => $this->date_paiement?->toISOString(),
            'date_validation'       => $this->date_validation?->toISOString(),
            'apprenant'             => $this->whenLoaded('apprenant') ? [
                'id'    => $this->apprenant->id,
                'prenom' => $this->apprenant->prenom,
                'nom'   => $this->apprenant->nom,
                'classe' => $this->apprenant->classe,
            ] : null,
            'frais'                 => $this->whenLoaded('fraisApprenant') ? [
                'id'   => $this->fraisApprenant->id,
                'nom'  => $this->fraisApprenant->categorieFrais?->nom,
            ] : null,
        ];
    }
}
