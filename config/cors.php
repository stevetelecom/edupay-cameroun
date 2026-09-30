<?php

/**
 * Configuration CORS de l'API.
 *
 * Ce fichier est INDISPENSABLE : le middleware global `HandleCors` est bien
 * enregistré par Laravel (voir `getGlobalMiddleware()`), mais sans ce fichier
 * `config('cors')` vaut `[]`. `Asm89\Stack\CorsService` retombe alors sur des
 * valeurs vides — `allowedOrigins => []` — et aucune origine n'est jamais
 * autorisée. La reponse part alors SANS en-tete
 * `Access-Control-Allow-Origin`, ce que le navigateur traduit par :
 *
 *   Access to XMLHttpRequest at '.../api/v1/auth/login' from origin
 *   'http://localhost:8099' has been blocked by CORS policy: No
 *   'Access-Control-Allow-Origin' header is present on the requested resource.
 *
 * Ce symptome est trompeur : la reponse du serveur est correcte (401 pour de
 * mauvais identifiants, 200 pour de bons), seule la verification cote navigateur
 * echoue. L'application mobile React Native (Expo) etait donc bloquee alors que
 * l'API, elle, fonctionnait.
 *
 * `supports_credentials` vaut volontairement `false` : l'API s'authentifie par
 * jeton Bearer (Sanctum), pas par cookie. C'est ce qui rend licite
 * `allowed_origins => ['*']`, la specification interdisant le couple
 * « origines en wildcard + credentials ».
 *
 * En production, si l'API doit devenir stricte, restreindre via .env :
 *   CORS_ALLOWED_ORIGINS=https://edupay.mekontso.gsi2026.com
 */
return [

    /*
     * Seuls ces chemins repondent aux requetes preflight (OPTIONS) et portent
     * les en-tetes CORS. L'API vit sous /api ; le back-office est servi sur la
     * meme origine et n'en a pas besoin.
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * `*` par defaut pour ne pas bloquer le developpement mobile : l'origine
     * change selon le port Expo (8081, 8099, 19006...), selon l'appareil et
     * selon le mode debug. Restreindre se fait par variable d'environnement.
     */
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))
    ))),

    /*
     * Motif regex pour les origines dont le domaine est variable, par exemple
     * un tunnel ngrok ou une IP de poste en developpement :
     *   CORS_ALLOWED_ORIGIN_PATTERNS="#^https://[a-z0-9-]+\.ngrok\.free$#i"
     */
    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGIN_PATTERNS', ''))
    ))),

    /*
     * `Authorization` est indispensable : sans lui, le preflight rejette la
     * requete port du jeton Bearer avant meme d'atteindre la route.
     */
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];