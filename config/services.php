<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    // Login Google untuk pasien (lihat docs/DECISIONS.md).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    /*
    | Otak chatbot: endpoint OpenAI-compatible. Bawaannya 9Router lokal supaya demo jalan
    | tanpa mengisi .env; kunci dan nama model bisa ditimpa lewat .env.
    | Rancangan: docs/SPEC-BOOKING-CHATBOT.md
    */
    'llm' => [
        'base_url' => env('LLM_BASE_URL', 'http://127.0.0.1:20128/v1'),
        'key' => env('LLM_API_KEY', '9router-lokal'),
        'model' => env('LLM_MODEL', 'cmc/moonshotai/Kimi-K2.7-Code-Highspeed'),
        'model_fallback' => env('LLM_MODEL_FALLBACK', 'cmc/zai-org/GLM-5.2-Fast'),
        'max_tokens' => (int) env('LLM_MAX_TOKENS', 1200),
        'timeout' => (int) env('LLM_TIMEOUT', 60),
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

];
