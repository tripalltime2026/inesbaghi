<?php

namespace App\Providers;

use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\BlogController;
use App\Http\Middleware\NoIndexPrivateArea;
use App\Http\Middleware\PublicSeo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BlogRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', PublicSeo::class])
            ->get('/blogi/{slug}', [BlogController::class, 'show'])
            ->where('slug', '[^/]+')
            ->name('public.blog.show');

        Route::middleware(['web', 'auth', NoIndexPrivateArea::class, 'role:admin'])
            ->post('/admin/content/blog/import', [AdminContentController::class, 'importBlog'])
            ->name('admin.content.blog.import');
    }
}
