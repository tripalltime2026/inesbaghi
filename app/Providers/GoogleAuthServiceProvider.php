<?php

namespace App\Providers;

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GoogleAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'throttle:20,1'])->group(function (): void {
            Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
                ->name('auth.google.redirect');
            Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
                ->name('auth.google.callback');
        });
    }
}
