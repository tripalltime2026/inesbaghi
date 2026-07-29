<?php
return [
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'otp_ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
        'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    ],
    'demo_auth' => [
        'enabled' => filter_var(env('DEMO_AUTH_ENABLED', true), FILTER_VALIDATE_BOOL),
        'admin_phone' => env('DEMO_ADMIN_PHONE', '555411831'),
        'auto_migrate' => filter_var(env('DEMO_AUTO_MIGRATE', true), FILTER_VALIDATE_BOOL),
    ],
];
