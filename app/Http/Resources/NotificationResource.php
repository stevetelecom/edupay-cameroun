<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'titre'      => $this->titre,
            'message'    => $this->message,
            'type'       => $this->type,
            'lu'         => $this->lu_at !== null,
            'lu_at'      => $this->lu_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
