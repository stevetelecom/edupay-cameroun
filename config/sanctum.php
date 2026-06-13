<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains — EduPay Cameroun
    |--------------------------------------------------------------------------
    | Domaines autorisés pour l'authentification Sanctum côté web (SPA/Blade).
    */
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,localhost:5173,127.0.0.1,127.0.0.1:8000,127.0.0.1:5173,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Garde d'authentification
    |--------------------------------------------------------------------------
    | Gardes à utiliser pour authentifier les requêtes Sanctum.
    */
    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration des tokens (en minutes). null = pas d'expiration.
    |--------------------------------------------------------------------------
    | Durée de validité des tokens d'accès personnel.
    */
    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    | Préfixe personnalisé pour les tokens EduPay.
    */
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'edupay_'),

];