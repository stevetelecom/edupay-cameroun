<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReclamationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'numero_ticket' => $this->numero_ticket,
            'sujet'        => $this->sujet,
            'description'  => $this->description,
            'statut'       => $this->statut,
            'reponse_admin'=> $this->reponse_admin,
            'paiement_id'  => $this->paiement_id,
            'resolu_le'    => $this->resolu_le?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
