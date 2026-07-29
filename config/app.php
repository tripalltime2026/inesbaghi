<?php

$demoAuthEnabled = filter_var(env('DEMO_AUTH_ENABLED', true), FILTER_VALIDATE_BOOL);
$demoFallbackKey = 'base64:SYlhdxhwXNerzu7RCntVrs659/ayZI+Y7krnXHJ2wuM=';

return [
    'name' => env('APP_NAME', 'ინეს ბაღი'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Tbilisi'),
    'locale' => env('APP_LOCALE', 'ka'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY') ?: ($demoAuthEnabled ? $demoFallbackKey : null),
    'previous_keys' => [...array_filter(explode(',', env('APP_PREVIOUS_KEYS', '')))],
    'maintenance' => ['driver' => 'file'],
];
