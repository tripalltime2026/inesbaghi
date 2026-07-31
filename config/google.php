<?php

return [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env(
        'GOOGLE_REDIRECT_URI',
        rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback',
    ),
    'legacy_phone_auth_enabled' => filter_var(
        env('LEGACY_PHONE_AUTH_ENABLED', false),
        FILTER_VALIDATE_BOOL,
    ),
];
