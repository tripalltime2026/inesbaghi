<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectResponsiveAssets
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || ! str_contains($content, '</head>')) {
            return $response;
        }

        $content = str_replace(
            'width=device-width, initial-scale=1',
            'width=device-width, initial-scale=1, viewport-fit=cover',
            $content,
        );

        if (! str_contains($content, '/css/mobile.css')) {
            $responsiveAssets = <<<'HTML'
    <meta name="theme-color" content="#FBF7EC">
    <link rel="stylesheet" href="/css/mobile.css?v=20260729">
HTML;

            $content = str_replace('</head>', $responsiveAssets."\n</head>", $content);
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
