<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametreSysteme extends Model
{
    protected $table = 'parametres_systeme';

    protected $fillable = ['cle', 'valeur'];

    const CACHE_KEY = 'parametres_systeme_all';

    /**
     * Recupere une valeur (avec cache 1h), ou la valeur par defaut si absente.
     */
    public static function obtenir(string $cle, $defaut = null)
    {
        $tous = Cache::remember(self::CACHE_KEY, 3600, function () {
            return self::pluck('valeur', 'cle')->toArray();
        });

        return array_key_exists($cle, $tous) ? $tous[$cle] : $defaut;
    }

    /**
     * Enregistre une ou plusieurs valeurs et invalide le cache.
     */
    public static function definir(array $paires): void
    {
        foreach ($paires as $cle => $valeur) {
            self::updateOrCreate(['cle' => $cle], ['valeur' => $valeur]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function obtenirBool(string $cle, bool $defaut = false): bool
    {
        $val = self::obtenir($cle, $defaut ? '1' : '0');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}
