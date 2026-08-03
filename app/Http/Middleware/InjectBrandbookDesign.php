<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectBrandbookDesign
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! str_contains($contentType, 'text/html') || ! method_exists($response, 'getContent')) {
            return $response;
        }

        $content = (string) $response->getContent();
        if (! str_contains($content, 'class="final-site"') || ! str_contains($content, '</head>')) {
            return $response;
        }

        $assets = [];

        if (! str_contains($content, '/css/brand-premium.css')) {
            $assets[] = '<link rel="preload" href="/images/ines-logo-horizontal.svg" as="image" type="image/svg+xml">';
            $assets[] = '<link rel="icon" href="/images/ines-logo-favicon.svg" type="image/svg+xml">';
            $assets[] = '<link rel="stylesheet" href="/css/brand-premium.css?v=20260803a">';
        }

        if (! str_contains($content, '/css/brand-premium-fixes.css')) {
            $assets[] = '<link rel="stylesheet" href="/css/brand-premium-fixes.css?v=20260803b">';
        }

        if ($assets !== []) {
            $content = str_replace('</head>', '    '.implode("\n    ", $assets)."\n</head>", $content);
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
