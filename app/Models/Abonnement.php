<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Abonnement extends Model
{
    protected $fillable = [
        'etablissement_id', 'plan', 'montant_mensuel',
        'date_debut', 'date_fin', 'grace_period_fin',
        'statut', 'reference_paiement', 'notes',
        'active_par', 'active_at',
    ];

    protected $casts = [
        'etablissement_id' => 'integer',
        'montant_mensuel'  => 'integer',
        'date_debut'       => 'date',
        'date_fin'         => 'date',
        'grace_period_fin' => 'date',
        'active_at'        => 'datetime',
    ];

    // Plans disponibles avec leurs caractéristiques
    const PLANS = [
        'basique' => [
            'nom'            => 'Basique',
            'montant'        => 5000,
            'max_apprenants' => 100,
            'sms_mensuel'    => 10,
            'multi_sites'    => false,
            'exports_cobac'  => false,
            'couleur'        => '#0D9E75',
        ],
        'standard' => [
            'nom'            => 'Standard',
            'montant'        => 10000,
            'max_apprenants' => 300,
            'sms_mensuel'    => -1, // illimité
            'multi_sites'    => true,
            'exports_cobac'  => false,
            'couleur'        => '#185FA5',
        ],
        'premium' => [
            'nom'            => 'Premium',
            'montant'        => 20000,
            'max_apprenants' => -1, // illimité
            'sms_mensuel'    => -1,
            'multi_sites'    => true,
            'exports_cobac'  => true,
            'couleur'        => '#E8A020',
        ],
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function activePar()
    {
        return $this->belongsTo(Admin::class, 'active_par');
    }

    public function estActif(): bool
    {
        return in_array($this->statut, ['actif', 'grace_period'])
            && Carbon::today()->lte($this->grace_period_fin);
    }

    public function joursRestants(): int
    {
        return max(0, Carbon::today()->diffInDays($this->date_fin, false));
    }

    public function enGracePeriod(): bool
    {
        return Carbon::today()->gt($this->date_fin)
            && Carbon::today()->lte($this->grace_period_fin);
    }

    public static function montantPlan(string $plan): int
    {
        return self::PLANS[$plan]['montant'] ?? 5000;
    }
}
