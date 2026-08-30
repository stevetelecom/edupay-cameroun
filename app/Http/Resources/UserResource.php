<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'prenom'             => $this->prenom,
            'nom'                => $this->nom,
            'nom_complet'        => $this->nom_complet,
            'email'              => $this->email,
            'telephone'          => $this->telephone,
            'ville'              => $this->ville,
            'quartier'           => $this->quartier,
            'profil'             => $this->profil,
            'role'               => $this->getRoleNames()->first(),
            'notif_sms'          => (bool) $this->notif_sms,
            'notif_email'        => (bool) $this->notif_email,
            'notif_rappel_echeance' => (bool) $this->notif_rappel_echeance,
            'suspendu'           => (bool) $this->suspendu,
            'etablissement_id'   => $this->etablissement_id,
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
