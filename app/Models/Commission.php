<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $fillable = [
        'paiement_id', 'etablissement_id',
        'montant_transaction', 'taux', 'montant_commission', 'statut',
        'montant_net_etablissement', 'frais_aangaraa',
        'reference_reversement', 'reversed_at',
    ];

    public function paiement() { return $this->belongsTo(Paiement::class); }
    public function etablissement() { return $this->belongsTo(Etablissement::class); }
}
