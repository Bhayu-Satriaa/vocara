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
        'model' => env('LLM_MODEL', 'cmc/zai-org/GLM-5.2-Fast'),
        'model_fallback' => env('LLM_MODEL_FALLBACK', 'cmc/moonshotai/Kimi-K2.7-Code-Highspeed'),
        'max_tokens' => (int) env('LLM_MAX_TOKENS', 800),
        // Kosongkan (bawaan) — jangan dikirim. Catatan uji 5 Nov 2026: menambahkan
        // reasoning_effort justru MEMICU jalur "berpikir" sehingga jawaban jadi 8-16 dtk,
        // sedangkan tanpa parameter itu hanya 1,5-2,5 dtk.
        'reasoning_effort' => env('LLM_REASONING_EFFORT'),
        // Jalur streaming (SSE) sudah tersedia, tapi di 9Router lokal ini terukur lebih
        // LAMBAT (8-17 dtk) daripada jalur biasa (1,5-2,2 dtk). Nyalakan hanya kalau
        // gateway-nya sudah membaik: CHAT_STREAM=true.
        'chat_stream' => (bool) env('CHAT_STREAM', false),
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
