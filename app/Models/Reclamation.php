<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reclamation extends Model
{
    protected $fillable = [
        'numero_ticket',
        'user_id',
        'paiement_id',
        'sujet',
        'description',
        'statut',
        'reponse_admin',
        'resolu_le',
    ];

    protected $casts = [
        'resolu_le' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($reclamation) {
            if (empty($reclamation->numero_ticket)) {
                $reclamation->numero_ticket = 'TCK-' . date('Y') . '-' . str_pad(
                    (static::max('id') ?? 0) + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    /**
     * Libellé traduit du statut.
     *
     * Les clés sont listées ici plutôt que construites dans les vues
     * (`__('admin.statut_'.$statut)`) : une clé concaténée échappe au contrôle
     * de traductions et s'affiche en clair si elle manque. Réutilise les clés
     * `ouvert` / `en_cours` / `resolu` / `rejete` déjà présentes.
     */
    public function statutLibelle(): string
    {
        return match ($this->statut) {
            'ouvert'   => __('admin.ouvert'),
            'en_cours' => __('admin.en_cours'),
            'resolu'   => __('admin.resolu'),
            'rejete'   => __('admin.rejete'),
            default    => (string) $this->statut,
        };
    }
}
