<?php

namespace App\Providers;

use App\Http\Controllers\Admin\UserRegistryController;
use App\Http\Middleware\NoIndexPrivateArea;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth', NoIndexPrivateArea::class, 'role:admin'])
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::patch('/users/{user}/access-payment', [UserRegistryController::class, 'update'])
                    ->name('users.access-payment.update');
            });

        $this->prepareRuntimeDirectories();

        if (
            app()->runningInConsole()
            || ! config('services.demo_auth.enabled')
            || ! config('services.demo_auth.auto_migrate')
        ) {
            return;
        }

        try {
            $this->prepareSqliteDatabase();

            if (! Schema::hasTable('users')) {
                Artisan::call('migrate', ['--force' => true]);
                Log::info('Demo database schema created automatically.');
            }
        } catch (Throwable $exception) {
            // The public website must remain renderable even when an external
            // database resource has not been attached yet. Authenticated and
            // write operations will still report their normal database error.
            Log::error('Demo database bootstrap failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function prepareRuntimeDirectories(): void
    {
        foreach ([
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ] as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0775, true, true);
            }
        }
    }

    private function prepareSqliteDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');
        if (! is_string($database) || $database === ':memory:' || File::exists($database)) {
            return;
        }

        File::ensureDirectoryExists(dirname($database), 0775, true);
        File::put($database, '');
    }
}
