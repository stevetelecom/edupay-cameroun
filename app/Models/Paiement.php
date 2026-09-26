<?php
namespace App\Models;

use App\Jobs\SendConfirmationPaiement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Paiement extends Model
{
    /** Statut d'attente : le payeur peut encore annuler. */
    public const STATUT_EN_ATTENTE = 'en_attente';

    /** Annulation definitive par le payeur : plus rien ne peut l'encaisser. */
    public const STATUT_ANNULE = 'annule';

    /** Statuts terminaux : plus aucune transition automatique. */
    public const STATUTS_TERMINAUX = ['valide', 'echoue', 'rembourse', 'annule'];

    protected $fillable = [
        'reference', 'user_id', 'apprenant_id', 'frais_apprenant_id',
        'echeancier_id', 'montant', 'frais_service', 'montant_total_paye',
        'frais_aangaraa', 'marge_edupay', 'mode_paiement', 'type_paiement',
        'numero_tranche', 'statut', 'telephone_paiement', 'pay_token',
        'aangaraa_transaction_id', 'operateur', 'annule_manuellement', 'date_paiement', 'date_validation',
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
        'annule_manuellement' => 'boolean',
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

    /** Le paiement a-t-il ete annule (statut ou drapeau) ? */
    public function estAnnule(): bool
    {
        return $this->statut === self::STATUT_ANNULE || (bool) $this->annule_manuellement;
    }

    /** Aucune transition automatique possible vers un autre statut. */
    public function estTerminal(): bool
    {
        return in_array($this->statut, self::STATUTS_TERMINAUX, true);
    }
}

