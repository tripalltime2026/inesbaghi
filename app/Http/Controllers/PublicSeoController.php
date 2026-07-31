<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Services\ManagedContent;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicSeoController extends Controller
{
    public function home(): View
    {
        return view('site');
    }

    public function show(string $page, ManagedContent $content): View
    {
        $config = config("seo.pages.{$page}");

        abort_unless(is_array($config), 404);

        if ($page === 'blog') {
            $content->ensureDefaults();

            return view('public.blog-index', [
                'pageKey' => $page,
                'page' => $config,
                'posts' => $this->publishedPosts()->get(),
            ]);
        }

        return view('public.seo-page', [
            'pageKey' => $page,
            'page' => $config,
            'pages' => config('seo.pages'),
        ]);
    }

    public function sitemap(ManagedContent $content): Response
    {
        $content->ensureDefaults();
        $baseUrl = rtrim((string) config('seo.site_url'), '/');
        $urls = collect(config('seo.pages'))
            ->map(fn (array $page): array => [
                'loc' => $baseUrl.$page['path'],
                'priority' => $page['priority'] ?? '0.7',
                'lastmod' => null,
            ])
            ->values();

        $articleUrls = $this->publishedPosts()
            ->get(['slug', 'updated_at'])
            ->map(fn (BlogPost $post): array => [
                'loc' => $baseUrl.'/blogi/'.$post->slug,
                'priority' => '0.7',
                'lastmod' => $post->updated_at?->toDateString(),
            ]);

        $urls = $urls->concat($articleUrls)->values();
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

    private function publishedPosts()
    {
        return BlogPost::query()
            ->where('status', 'published')
            ->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}
