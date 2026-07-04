<?php
namespace App\Models;

use App\Jobs\SendConfirmationPaiement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Paiement extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'apprenant_id', 'frais_apprenant_id',
        'echeancier_id', 'montant', 'frais_service', 'montant_total_paye',
        'frais_aangaraa', 'marge_edupay', 'mode_paiement', 'type_paiement',
        'numero_tranche', 'statut', 'telephone_paiement', 'pay_token',
        'aangaraa_transaction_id', 'operateur', 'date_paiement', 'date_validation',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'apprenant_id' => 'integer',
        'frais_apprenant_id' => 'integer',
        'echeancier_id' => 'integer',
        'numero_tranche' => 'integer',
        'montant' => 'integer',
        'frais_service' => 'integer',
        'montant_total_paye' => 'integer',
        'frais_aangaraa' => 'integer',
        'marge_edupay' => 'integer',
        'date_paiement' => 'datetime',
        'date_validation' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($paiement) {
            if (empty($paiement->reference)) {
                $paiement->reference = 'EP' . date('Y') . '-' . strtoupper(Str::random(5));
            }
        });

        // Confirmation envoyée uniquement dans PaiementController (webhook + verifierStatut)
    }

    public function user() { return $this->belongsTo(User::class); }
    public function apprenant() { return $this->belongsTo(Apprenant::class); }
    public function fraisApprenant() { return $this->belongsTo(FraisApprenant::class); }
    public function echeancier() { return $this->belongsTo(Echeancier::class); }
    public function transaction() { return $this->hasOne(Transaction::class); }
    public function commission() { return $this->hasOne(Commission::class); }
    public function remboursements() { return $this->hasMany(Remboursement::class); }
}

