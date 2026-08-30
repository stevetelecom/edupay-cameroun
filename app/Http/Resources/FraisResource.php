<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FraisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reste = (float) $this->montant_total - (float) $this->montant_paye;

        return [
            'id'              => $this->id,
            'apprenant_id'    => $this->apprenant_id,
            'categorie'       => $this->categorieFrais ? [
                'id'          => $this->categorieFrais->id,
                'nom'         => $this->categorieFrais->nom,
                'annee_scolaire' => $this->categorieFrais->annee_scolaire,
            ] : null,
            'montant_total'   => (float) $this->montant_total,
            'montant_paye'    => (float) $this->montant_paye,
            'reste'           => $reste,
            'statut'          => $this->statut,
            'annee_scolaire'  => $this->annee_scolaire,
            'echeanciers'     => EcheancierResource::collection($this->whenLoaded('categorieFrais')
                ? $this->categorieFrais->echeanciers
                : collect()),
        ];
    }
}
