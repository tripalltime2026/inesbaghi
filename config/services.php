<?php
return [
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'otp_ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
        'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    ],
    'demo_auth' => [
        'enabled' => (bool) env('DEMO_AUTH_ENABLED', false),
        'admin_phone' => env('DEMO_ADMIN_PHONE', '555411831'),
    ],
];