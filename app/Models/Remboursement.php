<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Remboursement extends Model
{
    protected $fillable = [
        'reference',
        'paiement_id',
        'montant',
        'motif',
        'statut',
        'initie_par',
        'traite_par',
        'traite_le',
        'motif_refus',
    ];

    protected $casts = [
        'traite_le' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($remboursement) {
            if (empty($remboursement->reference)) {
                $remboursement->reference = 'RB' . date('Y') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function initiateur()
    {
        return $this->belongsTo(User::class, 'initie_par');
    }

    public function traiteur()
    {
        return $this->belongsTo(User::class, 'traite_par');
    }
}
