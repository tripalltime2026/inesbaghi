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
        $isPublicSite = $request->routeIs('home');
        $isParentPortal = $request->routeIs('parent.dashboard');

        if ((! $isPublicSite && ! $isParentPortal) || ! method_exists($response, 'getContent')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        try {
            $html = (string) $response->getContent();

            if ($isPublicSite) {
                $html = $this->content->applyTextToHtml($html);
                $html = $this->injectScript($html, 'js/cms-public.js');
            }

            if ($isParentPortal) {
                $html = $this->injectScript($html, 'js/cms-portal.js');
            }

            $response->setContent($html);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function injectScript(string $html, string $path): string
    {
        if (str_contains($html, $path)) {
            return $html;
        }

        $script = '<script src="'.asset($path).'" defer></script>';

        return str_replace('</body>', $script."\n</body>", $html);
    }
}
