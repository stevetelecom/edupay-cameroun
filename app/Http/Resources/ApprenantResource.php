<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'nom'                     => $this->nom,
            'prenom'                  => $this->prenom,
            'nom_complet'             => trim($this->prenom . ' ' . $this->nom),
            'classe'                  => $this->classe,
            'matricule'               => $this->matricule,
            'date_naissance'          => $this->date_naissance
                ? ($this->date_naissance instanceof \DateTimeInterface
                    ? $this->date_naissance->format('Y-m-d')
                    : (string) substr($this->date_naissance, 0, 10))
                : null,
            'sexe'                    => $this->sexe,
            'statut_paiement'         => $this->statut_paiement,
            'valide_par_etablissement' => (bool) $this->valide_par_etablissement,
            'lien'                    => $this->pivot?->lien,
            'etablissement'           => new EtablissementResource($this->whenLoaded('etablissement')),
            'frais'                   => FraisResource::collection($this->whenLoaded('frais')),
        ];
    }
}
