<?php
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Apprenant> $apprenants
 */
class User extends Authenticatable
{
    protected $casts = [
        'etablissement_id' => 'integer',
    ];

    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'telephone',
        'ville',
        'quartier',
        'profil',
        'notif_sms',
        'notif_email',
        'notif_rappel_echeance',
        'password',
        'etablissement_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'notif_sms'         => 'boolean',
            'notif_email'       => 'boolean',
            'notif_rappel_echeance' => 'boolean',
        ];
    }

    // Accessor : nom complet (source unique de vérité, remplace l'ancien champ "name")
    public function getNomCompletAttribute(): string
    {
        $complet = trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
        return $complet !== '' ? $complet : 'Utilisateur';
    }

    // Alias pratique : permet d'utiliser ->name comme avant dans du code legacy,
    // sans avoir de colonne "name" en base (calculé à la volée).
    public function getNameAttribute(): string
    {
        return $this->nom_complet;
    }

    // Accessor : initiales pour l'avatar
    public function getInitialesAttribute(): string
    {
        $p = strtoupper(substr($this->prenom ?? '', 0, 1));
        $n = strtoupper(substr($this->nom ?? '', 0, 1));
        $initiales = $p . $n;

        return $initiales !== '' ? $initiales : '?';
    }

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function apprenants()
    {
        return $this->belongsToMany(Apprenant::class, 'user_apprenant')
                    ->withPivot('lien')
                    ->withTimestamps();
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }
}
