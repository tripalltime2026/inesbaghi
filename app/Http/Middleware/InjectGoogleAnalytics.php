<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectGoogleAnalytics
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $measurementId = trim((string) config('analytics.google_measurement_id'));
        if (! preg_match('/^G-[A-Z0-9]+$/', $measurementId)) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! preg_match('/<head(?:\s[^>]*)?>/i', $content)) {
            return $response;
        }

        if (str_contains($content, 'googletagmanager.com/gtag/js?id='.$measurementId)
            || str_contains($content, "gtag('config', '".$measurementId."')")) {
            return $response;
        }

        $escapedId = htmlspecialchars($measurementId, ENT_QUOTES, 'UTF-8');
        $tag = "\n<!-- Google tag (gtag.js) -->\n"
            .'<script async src="https://www.googletagmanager.com/gtag/js?id='.$escapedId.'"></script>'."\n"
            ."<script>\n"
            ."  window.dataLayer = window.dataLayer || [];\n"
            ."  function gtag(){dataLayer.push(arguments);}\n"
            ."  gtag('js', new Date());\n\n"
            ."  gtag('config', '".$escapedId."');\n"
            ."</script>\n";

        $content = preg_replace(
            '/(<head(?:\s[^>]*)?>)/i',
            '$1'.$tag,
            $content,
            1,
        ) ?? $content;

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
