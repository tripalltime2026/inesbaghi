<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicSeoController extends Controller
{
    public function home(): View
    {
        return view('site');
    }

    public function show(string $page): View
    {
        $config = config("seo.pages.{$page}");

        abort_unless(is_array($config), 404);

        return view('public.seo-page', [
            'pageKey' => $page,
            'page' => $config,
            'pages' => config('seo.pages'),
        ]);
    }

    public function sitemap(): Response
    {
        $baseUrl = rtrim((string) config('seo.site_url'), '/');
        $urls = collect(config('seo.pages'))
            ->map(fn (array $page): array => [
                'loc' => $baseUrl.$page['path'],
                'priority' => $page['priority'] ?? '0.7',
            ])
            ->values();

        $xml = view('public.sitemap', compact('urls'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots(): Response
    {
        $baseUrl = rtrim((string) config('seo.site_url'), '/');
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /parent/',
            'Disallow: /account',
            'Disallow: /auth/',
            'Disallow: /support/chat/',
            'Disallow: /content/public',
            '',
            "Sitemap: {$baseUrl}/sitemap.xml",
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
