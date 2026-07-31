<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectGoogleTagManager
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $containerId = trim((string) config('tag_manager.container_id'));
        if (! preg_match('/^GTM-[A-Z0-9]+$/', $containerId)) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content)) {
            return $response;
        }

        $escapedId = htmlspecialchars($containerId, ENT_QUOTES, 'UTF-8');
        $hasHeadTag = str_contains($content, 'googletagmanager.com/gtm.js')
            && str_contains($content, $containerId);
        $hasBodyTag = str_contains($content, 'googletagmanager.com/ns.html?id='.$containerId);

        if (! $hasHeadTag && preg_match('/<head(?:\s[^>]*)?>/i', $content)) {
            $headTag = "\n<!-- Google Tag Manager -->\n"
                ."<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n"
                ."new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n"
                ."j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n"
                ."'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n"
                ."})(window,document,'script','dataLayer','".$escapedId."');</script>\n"
                ."<!-- End Google Tag Manager -->\n";

            $content = preg_replace(
                '/(<head(?:\s[^>]*)?>)/i',
                '$1'.$headTag,
                $content,
                1,
            ) ?? $content;
        }

        if (! $hasBodyTag && preg_match('/<body(?:\s[^>]*)?>/i', $content)) {
            $bodyTag = "\n<!-- Google Tag Manager (noscript) -->\n"
                .'<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$escapedId.'"'."\n"
                .'height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>'."\n"
                ."<!-- End Google Tag Manager (noscript) -->\n";

            $content = preg_replace(
                '/(<body(?:\s[^>]*)?>)/i',
                '$1'.$bodyTag,
                $content,
                1,
            ) ?? $content;
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
