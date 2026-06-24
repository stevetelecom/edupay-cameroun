<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'paiement_id', 'reference_operateur', 'operateur',
        'montant', 'statut', 'payload_request', 'payload_response', 'callback_at',
    ];

    protected $casts = [
        'payload_request'  => 'array',
        'payload_response' => 'array',
        'callback_at'      => 'datetime',
    ];

    public function paiement() { return $this->belongsTo(Paiement::class); }
}
