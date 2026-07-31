<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GoogleConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $google = (array) config('google', []);
        $google['redirect'] = $google['redirect_uri'] ?? null;

        config()->set('services.google', $google);
        config()->set(
            'services.legacy_phone_auth.enabled',
            (bool) ($google['legacy_phone_auth_enabled'] ?? false),
        );
    }
}
