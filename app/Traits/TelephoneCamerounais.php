<?php

namespace App\Traits;

trait TelephoneCamerounais
{
    /**
     * Normalise un numéro camerounais saisi sous n'importe quel format
     * (+237 6XX XXX XXX, 237699123456, 6 99 12 34 56, etc.) vers 9 chiffres
     * bruts sans indicatif — format déjà utilisé dans toute l'application
     * (voir LoginController::login()).
     */
    private function normaliserTelephoneCm(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) > 9) {
            $digits = substr($digits, -9);
        }
        return $digits;
    }
}
