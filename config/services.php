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

    'supply_service' => [
        'base_url' => env('SUPPLY_SERVICE_BE_URL', 'http://127.0.0.1:8000'),
        'service_name' => env('SUPPLY_SERVICE_FE_CALLER_NAME', 'supply-fe'),
        'token' => env('SUPPLY_SERVICE_BE_TOKEN'),
        'verify_ssl' => filter_var(env('SUPPLY_SERVICE_BE_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],

    'platform_service' => [
        'base_url' => env('PLATFORM_SERVICE_BASE_URL', 'http://127.0.0.1:8020'),
    ],

    'platform_fe' => [
        'base_url' => env('PLATFORM_FE_BASE_URL'),
    ],

    'monolith_app' => [
        'base_url' => env('MONOLITH_BASE_URL', env('MONOLITH_AUTH_BASE_URL')),
    ],

    'keycloak' => [
        'base_url' => env('KEYCLOAK_BASE_URL'),
        'realm' => env('KEYCLOAK_REALM', 'kanggo'),
        'client_id' => env('KEYCLOAK_CLIENT_ID', 'supply-fe'),
        'verify_ssl' => filter_var(env('KEYCLOAK_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
        'ca_bundle' => env('KEYCLOAK_CA_BUNDLE'),
    ],

    'calculation_fe' => [
        'base_url' => env('CALCULATION_FE_BASE_URL'),
        'consume_path' => env('CALCULATION_FE_CONSUME_PATH', '/auth/consume'),
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];
