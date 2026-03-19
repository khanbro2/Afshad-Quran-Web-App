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

    'quran_foundation' => [
        'environment' => env('QURAN_FOUNDATION_ENV', 'prelive'),
        'base_url' => env(
            'QURAN_FOUNDATION_BASE_URL',
            env('QURAN_FOUNDATION_ENV', 'prelive') === 'production'
                ? 'https://apis.quran.foundation/content/api/v4'
                : 'https://apis-prelive.quran.foundation/content/api/v4'
        ),
        'auth_base_url' => env(
            'QURAN_FOUNDATION_AUTH_BASE_URL',
            env('QURAN_FOUNDATION_ENV', 'prelive') === 'production'
                ? 'https://oauth2.quran.foundation'
                : 'https://prelive-oauth2.quran.foundation'
        ),
        'client_id' => env('QURAN_FOUNDATION_CLIENT_ID'),
        'client_secret' => env('QURAN_FOUNDATION_CLIENT_SECRET'),
        'auth_token' => env('QURAN_FOUNDATION_AUTH_TOKEN'),
        'timeout' => env('QURAN_FOUNDATION_TIMEOUT', 30),
        'urdu_translation_resource_id' => env('QURAN_FOUNDATION_URDU_TRANSLATION_RESOURCE_ID'),
    ],

];
