<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedParentAccess
{
    public function handle(Request $request, Closure $next): Response|JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasVerifiedParentAccess()) {
            return $next($request);
        }

        $message = 'მშობელთა კლუბი ხელმისაწვდომია მხოლოდ იმ მშობლებისთვის, რომელთა ბავშვსაც ბაღში აქტიური ჩარიცხვა აქვს.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('account.show')->with('info', $message);
    }
}
