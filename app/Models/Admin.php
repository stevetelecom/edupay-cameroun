<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * Nom de la table — séparée des utilisateurs ordinaires.
     */
    protected $table = 'admins';

    /**
     * Garde Sanctum/Auth dédié.
     */
    protected $guard_name = 'admin';

    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'telephone',
        'password',
        'derniere_connexion',
        'derniere_connexion_ip',
        'est_actif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'derniere_connexion' => 'datetime',
        'est_actif'          => 'boolean',
        'password'           => 'hashed',
    ];

    // ── Accesseurs ────────────────────────────────────────────

    /**
     * Nom complet formaté.
     */
    public function getNomCompletAttribute(): string
    {
        return strtoupper($this->nom) . ' ' . ucfirst(strtolower($this->prenom));
    }

    /**
     * Initiales pour l'avatar.
     */
    public function getInitialesAttribute(): string
    {
        return strtoupper(
            mb_substr($this->prenom, 0, 1) . mb_substr($this->nom, 0, 1)
        );
    }

    // ── Relations ─────────────────────────────────────────────

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'acteur');
    }
}