<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PasswordReset extends Model
{
    protected $fillable = [
        'email',
        'guard',
        'code',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Scope : trouver les demandes non vérifiées et non expirées (15 minutes)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('is_verified', false)
            ->where('created_at', '>', now()->subMinutes(15));
    }

    /**
     * Scope : trouver par email et guard
     */
    public function scopeForEmail(Builder $query, string $email, string $guard = 'web'): Builder
    {
        return $query->where('email', $email)->where('guard', $guard);
    }

    /**
     * Générer un code à 6 chiffres unique
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->where('is_verified', false)->where('created_at', '>', now()->subMinutes(15))->exists());

        return $code;
    }

    /**
     * Marquer comme vérifié
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}
