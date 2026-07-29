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

        $headAssets = [];

        if (! str_contains($content, '/css/mobile.css')) {
            $headAssets[] = '<meta name="theme-color" content="#FBF7EC">';
            $headAssets[] = '<link rel="stylesheet" href="/css/mobile.css?v=20260729">';
        }

        if (! str_contains($content, '/css/experience-v2.css')) {
            $headAssets[] = '<link rel="stylesheet" href="/css/experience-v2.css?v=20260729b">';
        }

        if (! str_contains($content, '/css/home-mobile-v3.css')) {
            $headAssets[] = '<link rel="stylesheet" href="/css/home-mobile-v3.css?v=20260729c">';
        }

        if ($headAssets !== []) {
            $content = str_replace('</head>', '    '.implode("\n    ", $headAssets)."\n</head>", $content);
        }

        if (str_contains($content, '</body>')) {
            $scripts = [];
            if (! str_contains($content, '/js/experience-v2.js')) {
                $scripts[] = '<script src="/js/experience-v2.js?v=20260729b" defer></script>';
            }
            if (! str_contains($content, '/js/experience-v2-compat.js')) {
                $scripts[] = '<script src="/js/experience-v2-compat.js?v=20260729b" defer></script>';
            }
            if ($scripts !== []) {
                $content = str_replace('</body>', '    '.implode("\n    ", $scripts)."\n</body>", $content);
            }
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
