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

        $content = str_replace(
            'სასარგებლო რჩევები ყოველდღიური მშობლობისთვის',
            'ბლოგი მშობლებისთვის',
            $content,
        );

        $content = preg_replace(
            [
                '#<button(?=[^>]*data-page-target="blog")[^>]*>\s*ბლოგი\s*</button>#u',
                '#<button(?=[^>]*class="[^"]*ghost-button[^"]*")(?=[^>]*data-page-target="blog")[^>]*>\s*ყველა სტატია\s*→\s*</button>#u',
                '#<button(?=[^>]*class="[^"]*pill[^"]*butter[^"]*")(?=[^>]*data-page-target="admission")[^>]*>\s*ჩარიცხვა\s*</button>#u',
                '#<button(?=[^>]*class="[^"]*secondary-button[^"]*lavender[^"]*")(?=[^>]*data-page-target="admission")[^>]*>\s*ჩარიცხვის განაცხადი\s*</button>#u',
            ],
            [
                '<a href="/blogi">ბლოგი</a>',
                '<a class="ghost-button" href="/blogi">ყველა სტატია →</a>',
                '<button class="pill butter" type="button" data-page-target="admission">ვიზიტი</button>',
                '<button class="secondary-button lavender" type="button" data-page-target="admission">ვიზიტის დაგეგმვა</button>',
            ],
            $content,
        ) ?? $content;

        $assets = [];

        if (! str_contains($content, '/css/brand-premium.css')) {
            $assets[] = '<link rel="preload" href="/images/ines-logo-horizontal.svg" as="image" type="image/svg+xml">';
            $assets[] = '<link rel="icon" href="/images/ines-logo-favicon.svg" type="image/svg+xml">';
            $assets[] = '<link rel="stylesheet" href="/css/brand-premium.css?v=20260803a">';
        }

        if (! str_contains($content, '/css/brand-premium-fixes.css')) {
            $assets[] = '<link rel="stylesheet" href="/css/brand-premium-fixes.css?v=20260803b">';
        }

        if (! str_contains($content, '/css/brand-header-nav.css')) {
            $assets[] = '<link rel="stylesheet" href="/css/brand-header-nav.css?v=20260803d">';
        }

        if ($assets !== []) {
            $content = str_replace('</head>', '    '.implode("\n    ", $assets)."\n</head>", $content);
        }

        if (! str_contains($content, '/js/blog-navigation.js')) {
            $content = str_replace(
                '</body>',
                '    <script src="/js/blog-navigation.js?v=20260803b"></script>'."\n</body>",
                $content,
            );
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
