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

    /*
     * Accounts created by DeveloperLoginUsersSeeder and offered by the panel's
     * developer-login shortcuts. The DEV_LOGIN_* env vars already existed but
     * nothing read them — this key was missing, so config('services.dev_login.*')
     * resolved to null and the seeder tried to insert users with a null email,
     * violating the NOT NULL constraint on users.email.
     */
    'dev_login' => [
        'admin_email' => env('DEV_LOGIN_ADMIN_EMAIL', 'atef.ouerghi@spa-rac.org'),
        'scientist_email' => env('DEV_LOGIN_SCIENTIST_EMAIL', 'scientist@example.local'),
        'public_email' => env('DEV_LOGIN_PUBLIC_EMAIL', 'atef.ouerghi@gmail.com'),
    ],

    'cap' => [
        'site_key' => env('CAP_SITE_KEY'),
        'secret_key' => env('CAP_SECRET_KEY'),

        /*
         * Browser-facing endpoint. Keep it a root-relative path: Caddy reverse
         * proxies /cap/* to the cap container, so the widget calls the app's own
         * origin whatever hostname the user browsed to. An absolute URL here
         * pins the CAPTCHA to one hostname and breaks it under every other.
         */
        'public_url' => env('CAP_PUBLIC_URL', '/cap'),

        // Server-to-server, on the compose network — never reaches a browser.
        'internal_url' => env('CAP_INTERNAL_URL', 'http://cap:3000'),
    ],

];
