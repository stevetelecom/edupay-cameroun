<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reclamation extends Model
{
    protected $fillable = [
        'numero_ticket',
        'user_id',
        'paiement_id',
        'sujet',
        'description',
        'statut',
        'reponse_admin',
        'resolu_le',
    ];

    protected $casts = [
        'resolu_le' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($reclamation) {
            if (empty($reclamation->numero_ticket)) {
                $reclamation->numero_ticket = 'TCK-' . date('Y') . '-' . str_pad(
                    (static::max('id') ?? 0) + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }
}
