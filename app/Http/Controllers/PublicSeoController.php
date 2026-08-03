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

        $content->ensureDefaults();
        $config = $this->withManagedText($page, $config, $content->textValues());

        if ($page === 'blog') {
            return view('public.blog-index', [
                'pageKey' => $page,
                'page' => $config,
                'posts' => $this->publishedPosts()->get(),
            ]);
        }

        return view('public.seo-page', [
            'pageKey' => $page,
            'page' => $this->withManagedItems($page, $config, $content->publicPayload()),
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

    private function withManagedText(string $page, array $config, array $text): array
    {
        if ($page === 'about') {
            $config['h1'] = $text['about.title'] ?? $config['h1'];
            $config['lead'] = $text['about.paragraph_1'] ?? $config['lead'];
            $config['sections'] = [
                ['title' => 'ჩვენი გამოცდილება', 'body' => $text['about.paragraph_2'] ?? ''],
                ['title' => 'ბავშვის განვითარება', 'body' => $text['about.paragraph_3'] ?? ''],
                ['title' => 'ჩვენი ისტორია', 'body' => $text['about.story'] ?? ''],
                ['title' => 'ჩვენი ფილოსოფია', 'body' => $text['about.philosophy'] ?? ''],
                ['title' => 'ჩვენი ღირებულებები', 'body' => $text['about.values'] ?? ''],
            ];
        }

        if ($page === 'methodology') {
            $config['h1'] = $text['methodology.title'] ?? $config['h1'];
            $config['lead'] = $text['methodology.intro'] ?? $config['lead'];
            $sectionTitles = collect($config['sections'] ?? [])->pluck('title')->values();
            $config['sections'] = collect([
                $text['methodology.card_1_text'] ?? null,
                $text['methodology.card_2_text'] ?? null,
                $text['methodology.card_3_text'] ?? null,
            ])->filter(fn ($body): bool => filled($body))
                ->values()
                ->map(fn ($body, int $index): array => [
                    'title' => (string) ($sectionTitles->get($index) ?? 'მეთოდოლოგია'),
                    'body' => (string) $body,
                ])
                ->all();
        }

        if ($page === 'groups') {
            $config['lead'] = $text['catalog.groups_intro'] ?? $config['lead'];
        }

        if ($page === 'team') {
            $config['h1'] = $text['catalog.team_title'] ?? $config['h1'];
            $config['lead'] = $text['catalog.team_intro'] ?? $config['lead'];
        }

        if ($page === 'gallery') {
            $config['h1'] = $text['catalog.gallery_title'] ?? $config['h1'];
            $config['lead'] = $text['catalog.gallery_intro'] ?? $config['lead'];
        }

        if ($page === 'blog') {
            $config['h1'] = $text['catalog.blog_title'] ?? $config['h1'];
            $config['lead'] = $text['catalog.blog_intro'] ?? $config['lead'];
        }

        if ($page === 'faq') {
            $config['h1'] = $text['catalog.faq_title'] ?? $config['h1'];
            $config['lead'] = $text['catalog.faq_intro'] ?? $config['lead'];
        }

        if ($page === 'contact') {
            $config['h1'] = $text['contact.title'] ?? $config['h1'];
            $config['lead'] = $text['contact.intro'] ?? $config['lead'];
            $config['sections'] = [
                ['title' => 'მისამართი', 'body' => $text['contact.address'] ?? ''],
                ['title' => 'ტელეფონი', 'body' => $text['contact.phone_display'] ?? ''],
                ['title' => 'სამუშაო საათები', 'body' => $text['contact.hours'] ?? ''],
            ];
        }

        if ($page === 'admission') {
            $config['h1'] = $text['admission.title'] ?? $config['h1'];
            $config['lead'] = $text['admission.note_text'] ?? $config['lead'];
            $config['sections'] = array_merge([
                [
                    'title' => $text['admission.note_title'] ?? 'გმადლობთ ინტერესისთვის',
                    'body' => $text['admission.note_text'] ?? '',
                ],
            ], $config['sections'] ?? []);
        }

        return $config;
    }

    private function withManagedItems(string $page, array $config, array $payload): array
    {
        if ($page === 'groups') {
            $config['sections'] = collect($payload['group'] ?? [])
                ->map(function (array $item): array {
                    $details = [];
                    if (! empty($item['subtitle'])) {
                        $details[] = 'აღმზრდელი: '.$item['subtitle'];
                    }

                    $free = $item['meta']['free'] ?? null;
                    $total = $item['meta']['total'] ?? null;
                    if ($free !== null && $total !== null) {
                        $details[] = 'ხელმისაწვდომი ადგილები: '.$free.' / '.$total;
                    }

                    return [
                        'title' => (string) ($item['title'] ?? ''),
                        'body' => collect([
                            (string) ($item['body'] ?? ''),
                            implode(' · ', $details),
                        ])->filter()->implode(' '),
                    ];
                })
                ->values()
                ->all();
        }

        if ($page === 'team') {
            $config['sections'] = collect($payload['team'] ?? [])
                ->map(fn (array $item): array => [
                    'title' => (string) ($item['title'] ?? ''),
                    'body' => collect([
                        (string) ($item['subtitle'] ?? ''),
                        (string) ($item['body'] ?? ''),
                    ])->filter()->implode(' — '),
                ])
                ->values()
                ->all();
        }

        if ($page === 'faq') {
            $config['faqs'] = collect($payload['faq'] ?? [])
                ->map(fn (array $item): array => [
                    'question' => (string) ($item['title'] ?? ''),
                    'answer' => (string) ($item['body'] ?? ''),
                ])
                ->values()
                ->all();
        }

        return $config;
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
