<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectSocialFooterLinks
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof Response || ! $this->isHtmlResponse($response)) {
            return $response;
        }

        $html = $response->getContent();

        if (! is_string($html) || ! str_contains($html, '</footer>') || str_contains($html, 'data-social-footer')) {
            return $response;
        }

        $stylesheet = <<<'HTML'
<link rel="stylesheet" href="/css/social-footer.css?v=20260731">
HTML;

        $socialLinks = <<<'HTML'
<div class="footer-social" data-social-footer aria-label="ინეს ბაღი სოციალურ ქსელებში">
    <span class="footer-social-title">გამოგვყევით სოციალურ ქსელებში</span>
    <div class="footer-social-links">
        <a href="https://www.facebook.com/Inesbaghi" target="_blank" rel="noopener noreferrer" aria-label="ინეს ბაღი Facebook-ზე" title="Facebook">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 8.5V7c0-.8.5-1 1.1-1H17V3h-2.5C11.8 3 10 4.7 10 7v1.5H7V12h3v9h4v-9h2.7l.5-3.5H14Z"/></svg>
            <span>Facebook</span>
        </a>
        <a href="https://www.instagram.com/ines_baghi/" target="_blank" rel="noopener noreferrer" aria-label="ინეს ბაღი Instagram-ზე" title="Instagram">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M7.2 2h9.6A5.2 5.2 0 0 1 22 7.2v9.6a5.2 5.2 0 0 1-5.2 5.2H7.2A5.2 5.2 0 0 1 2 16.8V7.2A5.2 5.2 0 0 1 7.2 2Zm0 3A2.2 2.2 0 0 0 5 7.2v9.6A2.2 2.2 0 0 0 7.2 19h9.6a2.2 2.2 0 0 0 2.2-2.2V7.2A2.2 2.2 0 0 0 16.8 5H7.2Zm9.95 1.35a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 3a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" clip-rule="evenodd"/></svg>
            <span>Instagram</span>
        </a>
    </div>
</div>
HTML;

        if (! str_contains($html, '/css/social-footer.css')) {
            $html = preg_replace('/<\/head>/i', $stylesheet."\n</head>", $html, 1) ?? $html;
        }

        $html = preg_replace('/<\/footer>/i', $socialLinks."\n</footer>", $html, 1) ?? $html;
        $response->setContent($html);

        return $response;
    }

    private function isHtmlResponse(Response $response): bool
    {
        return str_contains(strtolower((string) $response->headers->get('Content-Type')), 'text/html');
    }
}
