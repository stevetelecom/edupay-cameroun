<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],
    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'africastalking' => [
        'username'  => env('AT_USERNAME', 'sandbox'),
        'api_key'   => env('AT_API_KEY', ''),
        'sender_id' => env('AT_SENDER_ID', ''),
    ],
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect'      => env('GOOGLE_REDIRECT_URI', rtrim(env('APP_URL', 'http://localhost'), '/') . '/auth/google/callback'),
    ],
    'aangaraa' => [
        'api_url'     => env('AANGARAA_API_URL', 'https://api-production.aangaraa-pay.com/api/v1'),
        'app_key'     => env('AANGARAA_APP_KEY', ''),
        // URL webhook explicite en prod (évite localhost si APP_URL mal configuré)
        'notify_url'  => env('AANGARAA_NOTIFY_URL'),
    ],

    // Securite (M-05 audit) : adresse d'alerte pour incidents critiques
    // (webhook suspect, etc.) — plus d'email personnel code en dur.
    'admin_alert_email' => env('ADMIN_ALERT_EMAIL', 'admin@edupay.cm'),

    /*
    |--------------------------------------------------------------------------
    | EduPay — Parametres systeme (S07 CDC)
    |--------------------------------------------------------------------------
    | Modes de paiement actifs, langue, taux, notifications.
    | Lus depuis .env (EDUPAY_*), modifiables via Admin > Parametres systeme.
    */
    'edupay' => [
        'taux_commission'  => (float) env('EDUPAY_TAUX_COMMISSION', 0.025),
        'timeout_paiement' => (int) env('EDUPAY_TIMEOUT_PAIEMENT', 120),
        'max_tranches'     => (int) env('EDUPAY_MAX_TRANCHES', 3),
        'sms_actif'        => env('EDUPAY_SMS_ACTIF', true),
        'maintenance'      => env('EDUPAY_MAINTENANCE', false),
        'mtn_actif'        => env('EDUPAY_MTN_ACTIF', true),
        'orange_actif'     => env('EDUPAY_ORANGE_ACTIF', true),
        'langue_defaut'    => env('EDUPAY_LANGUE_DEFAUT', 'fr'),
    ],
];
