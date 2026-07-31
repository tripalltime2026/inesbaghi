<?php

namespace App\Providers;

use App\Http\Controllers\HomeHeroAssetController;
use App\Http\Middleware\NoIndexPrivateArea;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HomeHeroAssetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->get('/content/home-hero', [HomeHeroAssetController::class, 'image'])
            ->name('content.home-hero');

        Route::middleware(['web', 'auth', NoIndexPrivateArea::class, 'role:admin'])
            ->put('/admin/content/hero', [HomeHeroAssetController::class, 'update'])
            ->name('admin.content.hero.update');
    }
}
