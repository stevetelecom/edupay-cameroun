<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit U — trace des relances d'impayes envoyees aux parents.
 *
 * Sert a deux choses :
 *  - empecher le spam : une relance pour un couple (ligne de frais, parent)
 *    ne peut pas etre reenvoyee avant `delai()` ;
 *  - alimenter `derniere_relance` de l'endpoint impayes, qui valait null
 *    faute de trace.
 *
 * @property int         $id
 * @property int         $frais_apprenant_id
 * @property int         $apprenant_id
 * @property int         $etablissement_id
 * @property int|null    $user_id
 * @property string      $canal
 * @property string      $statut
 * @property string|null $erreur
 */
class RelanceImpaye extends Model
{
    protected $table = 'relances_impayes';

    protected $fillable = [
        'frais_apprenant_id',
        'apprenant_id',
        'etablissement_id',
        'user_id',
        'canal',
        'statut',
        'erreur',
    ];

    /** Delai anti-spam par defaut : 24 h. */
    public const DELAI_ANTI_SPAM_H = 24;

    public function fraisApprenant(): BelongsTo
    {
        return $this->belongsTo(FraisApprenant::class);
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeEnvoyees(Builder $query): Builder
    {
        return $query->where('statut', 'envoye');
    }

    /**
     * Derniere relance envoyee a ce parent pour cette ligne de frais.
     */
    public function scopeDernierePour(Builder $query, int $fraisApprenantId, ?int $userId): Builder
    {
        return $query->where('frais_apprenant_id', $fraisApprenantId)
            ->where('user_id', $userId)
            ->envoyees();
    }

    /**
     * Une relance a-t-elle deja ete envoyee dans le delai anti-spam ?
     */
    public static function envoyeeRecently(int $fraisApprenantId, ?int $userId, int $heures = self::DELAI_ANTI_SPAM_H): bool
    {
        return static::query()
            ->where('frais_apprenant_id', $fraisApprenantId)
            ->where('user_id', $userId)
            ->envoyees()
            ->where('created_at', '>=', now()->subHours($heures))
            ->exists();
    }
}
