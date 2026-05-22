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

    'calculation_fe' => [
        'base_url' => env('CALCULATION_FE_BASE_URL'),
        'consume_path' => env('CALCULATION_FE_CONSUME_PATH', '/auth/consume'),
    ],

    'auth_handoff' => [
        'enabled' => filter_var(env('AUTH_HANDOFF_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],

    'monolith_auth' => [
        'enabled' => filter_var(env('MONOLITH_AUTH_ENABLED', false), FILTER_VALIDATE_BOOL),
        'base_url' => env('MONOLITH_AUTH_BASE_URL'),
        'handoff_start_path' => env('MONOLITH_AUTH_HANDOFF_START_PATH', '/auth/handoff/start'),
        'handoff_redeem_path' => env('MONOLITH_AUTH_HANDOFF_REDEEM_PATH', '/api/internal/auth/handoffs/redeem'),
        'handoff_logout_path' => env('MONOLITH_AUTH_HANDOFF_LOGOUT_PATH', '/auth/handoff/logout'),
        'verify_ssl' => filter_var(env('MONOLITH_AUTH_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];
