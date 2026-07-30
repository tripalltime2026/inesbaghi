<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentClubAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->canAccessParentClub()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'მშობელთა კლუბი ხელმისაწვდომია მხოლოდ აქტიურად ჩარიცხული ბავშვის დადასტურებული მშობლისთვის.',
                'account_status_url' => route('account.status'),
            ], 403);
        }

        return new RedirectResponse(route('account.status'));
    }
}
