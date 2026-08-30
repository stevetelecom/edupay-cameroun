<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcheancierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero_tranche' => $this->numero_tranche,
            'montant'        => (float) $this->montant,
            'date_echeance'  => $this->date_echeance?->format('Y-m-d'),
            'libelle'        => $this->libelle,
        ];
    }
}
