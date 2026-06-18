<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Paiement extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'apprenant_id', 'frais_apprenant_id',
        'echeancier_id', 'montant', 'mode_paiement', 'type_paiement',
        'numero_tranche', 'statut', 'telephone_paiement',
        'date_paiement', 'date_validation',
    ];

    protected static function booted(): void
    {
        static::creating(function ($paiement) {
            if (empty($paiement->reference)) {
                $paiement->reference = 'EP' . date('Y') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function apprenant() { return $this->belongsTo(Apprenant::class); }
    public function fraisApprenant() { return $this->belongsTo(FraisApprenant::class); }
    public function echeancier() { return $this->belongsTo(Echeancier::class); }
    public function transaction() { return $this->hasOne(Transaction::class); }
    public function commission() { return $this->hasOne(Commission::class); }
}

