<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectMobileUxFixes
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($request->routeIs('auth.mode') && $response instanceof JsonResponse) {
            $payload = $response->getData(true);
            unset($payload['admin_phone']);
            $response->setData($payload);

            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, 'final-site')) {
            return $response;
        }

        $styles = [];
        if (! str_contains($content, '/css/mobile-fixes-v5.css')) {
            $styles[] = '<link rel="stylesheet" href="/css/mobile-fixes-v5.css?v=20260731c">';
        }
        if (! str_contains($content, '/css/mobile-stability-v6.css')) {
            $styles[] = '<link rel="stylesheet" href="/css/mobile-stability-v6.css?v=20260731a">';
        }
        if ($styles !== []) {
            $content = str_replace('</head>', '    '.implode("\n    ", $styles)."\n</head>", $content);
        }

        if (! str_contains($content, '/js/mobile-fixes-v5.js')) {
            $content = str_replace(
                '</body>',
                '    <script src="/js/mobile-fixes-v5.js?v=20260731c" defer></script>'."\n</body>",
                $content,
            );
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
