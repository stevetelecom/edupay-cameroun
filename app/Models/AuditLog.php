<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    // Pas de mise à jour — un log est immuable
    const UPDATED_AT = null;

    protected $fillable = [
        'acteur_type',
        'acteur_id',
        'action',
        'detail',
        'ip_address',
        'user_agent',
        'niveau',
        'donnees_avant',
        'donnees_apres',
        'created_at',
    ];

    protected $casts = [
        'created_at'    => 'datetime',
        'donnees_avant' => 'array',
        'donnees_apres' => 'array',
    ];

    // ── Méthodes statiques d'enregistrement ───────────────────

    /**
     * Enregistre un événement audit avec un acteur authentifié.
     */
    public static function enregistrer(
        Model   $acteur,
        string  $action,
        string  $detail,
        Request $request,
        string  $niveau = 'INFO',
        ?array  $donnees_avant = null,
        ?array  $donnees_apres = null
    ): self {
        return static::create([
            'acteur_type'   => get_class($acteur),
            'acteur_id'     => $acteur->getKey(),
            'action'        => $action,
            'detail'        => $detail,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'niveau'        => $niveau,
            'donnees_avant' => $donnees_avant,
            'donnees_apres' => $donnees_apres,
            'created_at'    => now(),
        ]);
    }

    /**
     * Enregistre un événement audit sans acteur (ex: tentative de connexion inconnue).
     */
    public static function enregistrerSansUser(
        string  $action,
        string  $detail,
        Request $request,
        string  $niveau = 'WARNING'
    ): self {
        return static::create([
            'acteur_type' => 'anonyme',
            'acteur_id'   => null,
            'action'      => $action,
            'detail'      => $detail,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'niveau'      => $niveau,
            'created_at'  => now(),
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeCritiques($query)
    {
        return $query->where('niveau', 'CRITICAL');
    }

    public function scopeAujourdhui($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ── Accesseurs ────────────────────────────────────────────

    public function getNiveauBadgeClassAttribute(): string
    {
        return match ($this->niveau) {
            'CRITICAL' => 'bg-red-100 text-red-800',
            'WARNING'  => 'bg-yellow-100 text-yellow-800',
            'INFO'     => 'bg-green-100 text-green-800',
            default    => 'bg-gray-100 text-gray-800',
        };
    }

    // ── Relations ─────────────────────────────────────────────

    public function acteur()
    {
        return $this->morphTo();
    }
}