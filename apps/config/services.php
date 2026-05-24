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

    'greenapi' => [
        'instance_id' => env('GREENAPI_INSTANCE_ID'),
        'token' => env('GREENAPI_TOKEN'),
    ],

    'dev_login' => [
        'allowed_ips' => env('DEV_LOGINS_ALLOWED_IPS', '127.0.0.1'),
        'admin_email' => env('DEV_LOGIN_ADMIN_EMAIL', 'admin@example.local'),
        'scientist_email' => env('DEV_LOGIN_SCIENTIST_EMAIL', 'scientist@mamias.local'),
        'public_email' => env('DEV_LOGIN_PUBLIC_EMAIL', 'user@example.local'),
    ],

    'cap' => [
        'site_key' => env('CAP_SITE_KEY'),
        'secret_key' => env('CAP_SECRET_KEY'),
        'public_url' => env('CAP_PUBLIC_URL', 'http://localhost:3000'),
        'internal_url' => env('CAP_INTERNAL_URL', 'http://cap:3000'),
    ],

];
