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

        // Proveedor de la IA: gemini (necesita internet) u ollama (local, sin internet)
        'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
    ],

        'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:3b'),
        'timeout' => env('OLLAMA_TIMEOUT', 120),
        'contexto' => env('OLLAMA_CONTEXTO', 8192),
    ],

        'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    ],
    // Captcha local (funciona sin internet). CAPTCHA_ENABLED=false lo apaga.
    'captcha' => [
        'enabled' => env('CAPTCHA_ENABLED', true),
    ],

    'backups' => [
        'admin_key' => env('BACKUP_ADMIN_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];