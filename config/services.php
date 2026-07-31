<?php
return [
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'otp_ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
        'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    ],
    'demo_auth' => [
        'enabled' => filter_var(env('DEMO_AUTH_ENABLED', true), FILTER_VALIDATE_BOOL),
        'admin_phone' => env('DEMO_ADMIN_PHONE'),
        'auto_migrate' => filter_var(env('DEMO_AUTO_MIGRATE', true), FILTER_VALIDATE_BOOL),
    ],
    'ines_ai' => [
        'enabled' => filter_var(env('INES_AI_ENABLED', false), FILTER_VALIDATE_BOOL),
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('INES_AI_MODEL', 'gpt-5-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
        'show_exact_availability' => filter_var(env('INES_AI_SHOW_EXACT_AVAILABILITY', false), FILTER_VALIDATE_BOOL),
    ],
];
