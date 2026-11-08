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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sso' => [
        'driver' => env('SSO_DRIVER', 'keycloak'),
        'base_url' => env('BPS_SSO_BASE_URL', env('SSO_BASE_URL', 'https://sso.bps.go.id')),
        'realm' => env('BPS_SSO_REALM', env('SSO_REALM', 'pegawai-bps')),
        'client_id' => env('BPS_SSO_CLIENT_ID', env('SSO_CLIENT_ID', '13328-elka-nv1')),
        'client_secret' => env('BPS_SSO_CLIENT_SECRET', env('SSO_CLIENT_SECRET', '')),
        'redirect_uri' => env('BPS_SSO_REDIRECT_URI', env('SSO_REDIRECT_URI', env('APP_URL', 'http://localhost:8000').'/auth/sso/callback')),
        'timeout' => (float) env('SSO_TIMEOUT', 10.0),
    ],

    'seeder' => [
        'superadmin' => [
            'name' => env('SEEDER_SUPERADMIN_NAME', 'Super Administrator Awal'),
            'email' => env('SEEDER_SUPERADMIN_EMAIL'),
            'password' => env('SEEDER_SUPERADMIN_PASSWORD'),
            'nip' => env('SEEDER_SUPERADMIN_NIP', '198501012010011001'),
        ],
    ],

];
