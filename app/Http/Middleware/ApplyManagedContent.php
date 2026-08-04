<?php

namespace App\Http\Middleware;

use App\Services\ManagedContent;
use App\Services\RestrictedTerminology;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyManagedContent
{
    public function __construct(
        private readonly ManagedContent $content,
        private readonly RestrictedTerminology $terminology,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent')) {
            return $response;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        $isHtml = $contentType === '' || str_contains($contentType, 'text/html');
        $isTextResponse = $isHtml
            || str_contains($contentType, 'application/json')
            || str_contains($contentType, 'application/xml')
            || str_contains($contentType, 'text/xml')
            || str_contains($contentType, 'text/plain')
            || str_contains($contentType, 'javascript');

        if (! $isTextResponse) {
            return $response;
        }

        $isHome = $request->routeIs('home');
        $isManagedPublicHtml = $request->routeIs(
            'home',
            'public.*',
            'privacy',
            'terms',
            'privacy.request',
            'auth.credentials.login.form',
            'auth.credentials.register.form',
        );
        $isParentPortal = $request->routeIs('parent.dashboard');

        try {
            $body = (string) $response->getContent();

            if ($isHtml && $isManagedPublicHtml) {
                $body = $this->content->applyTextToHtml($body);
            }

            if ($isHtml && $isHome) {
                $body = $this->injectScript($body, 'js/cms-public.js?v=20260804a');
            }

            if ($isHtml && $isParentPortal) {
                $body = $this->injectScript($body, 'js/cms-portal.js');
            }

            $body = $this->terminology->sanitize($body);

            $response->setContent($body);
            $response->headers->remove('Content-Length');

            if ($isManagedPublicHtml) {
                $response->headers->set('Cache-Control', 'public, max-age=0, must-revalidate');
            }
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
