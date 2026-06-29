<?php
namespace App\Models;

use App\Jobs\SendConfirmationPaiement;
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

    protected $casts = [
        'user_id' => 'integer',
        'apprenant_id' => 'integer',
        'frais_apprenant_id' => 'integer',
        'echeancier_id' => 'integer',
        'numero_tranche' => 'integer',
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

        // Dispatcher l'événement quand le statut devient 'valide'
        static::updated(function ($paiement) {
            if ($paiement->isDirty('statut') && $paiement->statut === 'valide') {
                // Envoyer confirmation de paiement (Email + SMS)
                dispatch(new SendConfirmationPaiement($paiement));
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function apprenant() { return $this->belongsTo(Apprenant::class); }
    public function fraisApprenant() { return $this->belongsTo(FraisApprenant::class); }
    public function echeancier() { return $this->belongsTo(Echeancier::class); }
    public function transaction() { return $this->hasOne(Transaction::class); }
    public function commission() { return $this->hasOne(Commission::class); }
    public function remboursements() { return $this->hasMany(Remboursement::class); }
}

