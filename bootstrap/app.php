<?php

use App\Http\Middleware\ApplyManagedContent;
use App\Http\Middleware\EnsureParentClubAccess;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\InjectBrandbookDesign;
use App\Http\Middleware\InjectGoogleAnalytics;
use App\Http\Middleware\InjectGoogleTagManager;
use App\Http\Middleware\InjectHomeHeroManager;
use App\Http\Middleware\InjectMobileUxFixes;
use App\Http\Middleware\InjectResponsiveAssets;
use App\Http\Middleware\InjectSocialFooterLinks;
use App\Http\Middleware\PasswordAuthentication;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn (Request $request): string => route('auth.credentials.login.form'),
        );
        $middleware->web(append: [
            InjectBrandbookDesign::class,
            InjectGoogleTagManager::class,
            InjectGoogleAnalytics::class,
            PasswordAuthentication::class,
            InjectMobileUxFixes::class,
            InjectResponsiveAssets::class,
            InjectSocialFooterLinks::class,
            ApplyManagedContent::class,
            InjectHomeHeroManager::class,
        ]);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'parent.club' => EnsureParentClubAccess::class,
        ]);
    })
    ->withExceptions(fn (Exceptions $exceptions) => null)
    ->create();
