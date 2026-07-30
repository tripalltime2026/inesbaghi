<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoIndexPrivateArea
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $contentType = (string) $response->headers->get('Content-Type', '');
        $content = $response->getContent();

        if (str_contains($contentType, 'text/html') && is_string($content) && str_contains($content, '</head>')) {
            if (! str_contains($content, 'name="robots"')) {
                $content = str_replace(
                    '</head>',
                    "    <meta name=\"robots\" content=\"noindex,nofollow,noarchive\">\n</head>",
                    $content,
                );
                $response->setContent($content);
                $response->headers->remove('Content-Length');
            }
        }

        return $response;
    }
}
