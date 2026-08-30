<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EtablissementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'code_etablissement'  => $this->code_etablissement,
            'nom'                 => $this->nom,
            'logo'                => $this->logo ? asset('storage/' . $this->logo) : null,
            'type'                => $this->type,
            'region'              => $this->region,
            'ville'               => $this->ville,
            'quartier'            => $this->quartier,
            'telephone'           => $this->telephone,
            'email'               => $this->email,
            'site_web'            => $this->site_web,
            'statut'              => $this->statut,
        ];
    }
}
