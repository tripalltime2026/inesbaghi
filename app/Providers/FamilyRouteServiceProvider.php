<?php

namespace App\Providers;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Middleware\NoIndexPrivateArea;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FamilyRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', NoIndexPrivateArea::class])
            ->post('/account/children', [AccountController::class, 'storeChild'])
            ->name('account.children.store');

        Route::middleware(['web', 'auth', 'role:admin', NoIndexPrivateArea::class])
            ->prefix('admin/families')
            ->name('admin.families.')
            ->group(function (): void {
                Route::get('/create', [FamilyController::class, 'create'])->name('create');
                Route::post('/', [FamilyController::class, 'store'])->name('store');
            });
    }
}
