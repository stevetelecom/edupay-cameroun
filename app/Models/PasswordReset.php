<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class PasswordReset extends Model
{
    protected $fillable = [
        'email',
        'guard',
        'code',
        'tentatives',
        'is_verified',
        'verified_at',
        'reset_token',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'tentatives'  => 'integer',
    ];

    protected $hidden = ['code'];

    const MAX_TENTATIVES = 5;

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('is_verified', false)
            ->where('created_at', '>', now()->subMinutes(15));
    }

    public function scopeForEmail(Builder $query, string $email, string $guard = 'web'): Builder
    {
        return $query->where('email', $email)->where('guard', $guard);
    }

    /**
     * Génère un code brut à 6 chiffres (à hasher avant stockage, jamais loggé).
     */
    public static function genererCodeBrut(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Crée un enregistrement avec le code déjà hashé.
     * Retourne le code EN CLAIR (uniquement pour l'envoyer par email, jamais stocké/loggé ainsi).
     */
    public static function creerPour(string $email, string $guard): string
    {
        $codeClair = self::genererCodeBrut();

        self::create([
            'email'      => $email,
            'guard'      => $guard,
            'code'       => Hash::make($codeClair),
            'tentatives' => 0,
        ]);

        return $codeClair;
    }

    /**
     * Vérifie un code candidat contre le hash stocké, avec limite de tentatives.
     * Retourne le record si le code est correct, sinon null.
     * Incrémente le compteur de tentatives à chaque échec et invalide après MAX_TENTATIVES.
     */
    public static function verifierCode(string $email, string $codeCandidat): ?self
    {
        $record = self::where('email', $email)
            ->pending()
            ->where('tentatives', '<', self::MAX_TENTATIVES)
            ->latest()
            ->first();

        if (! $record) {
            return null;
        }

        if (Hash::check($codeCandidat, $record->code)) {
            return $record;
        }

        $record->increment('tentatives');
        return null;
    }

    public function markAsVerified(): void
    {
        // Securite (M-01 audit) : genere un token aleatoire imprevisible (64 hex),
        // utilise a la place de l'ID auto-incremente dans le lien envoye au client.
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'reset_token' => bin2hex(random_bytes(32)),
        ]);
    }

    /**
     * Retrouve un enregistrement verifie et non expire a partir de son token public.
     * Remplace PasswordReset::find($id) — l'ID n'est plus utilisable comme identifiant
     * externe. Expire 15 minutes apres verification (verified_at), pas apres creation.
     */
    public static function trouverParToken(string $token): ?self
    {
        return self::where('reset_token', $token)
            ->where('is_verified', true)
            ->where('verified_at', '>', now()->subMinutes(15))
            ->first();
    }
}
