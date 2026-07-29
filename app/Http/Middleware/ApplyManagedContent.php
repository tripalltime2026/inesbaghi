<?php

namespace App\Http\Middleware;

use App\Services\ManagedContent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyManagedContent
{
    public function __construct(private readonly ManagedContent $content)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('home') || ! method_exists($response, 'getContent')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        try {
            $html = (string) $response->getContent();
            $html = $this->content->applyTextToHtml($html);

            if (! str_contains($html, 'cms-public.js')) {
                $script = '<script src="'.asset('js/cms-public.js').'" defer></script>';
                $html = str_replace('</body>', $script."\n</body>", $html);
            }

            $response->setContent($html);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
