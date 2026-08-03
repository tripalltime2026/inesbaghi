<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Services\ManagedContent;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicSeoController extends Controller
{
    public function home(): View
    {
        $latestPosts = Schema::hasTable((new BlogPost)->getTable())
            ? $this->publishedPosts()->limit(3)->get()
            : collect();

        return view('site', compact('latestPosts'));
    }

    public function show(string $page, ManagedContent $content): View
    {
        $config = config("seo.pages.{$page}");

        abort_unless(is_array($config), 404);

        $content->ensureDefaults();
        $defaults = collect($content->textDefinitions())->pluck('default', 'key')->all();
        $config = $this->withManagedText($page, $config, $content->textValues(), $defaults);

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

    private function withManagedText(string $page, array $config, array $text, array $defaults): array
    {
        if ($page === 'about') {
            $config['h1'] = $this->changedValue('about.title', $text, $defaults, $config['h1']);
            $config['lead'] = $this->changedValue('about.paragraph_1', $text, $defaults, $config['lead']);
            $config['sections'][0]['body'] = $this->changedValue(
                'about.paragraph_2',
                $text,
                $defaults,
                $config['sections'][0]['body'] ?? '',
            );
            $config['sections'][1]['body'] = $this->changedValue(
                'about.paragraph_3',
                $text,
                $defaults,
                $config['sections'][1]['body'] ?? '',
            );

            foreach ([
                'about.story' => 'ჩვენი ისტორია',
                'about.philosophy' => 'ჩვენი ფილოსოფია',
                'about.values' => 'ჩვენი ღირებულებები',
            ] as $key => $title) {
                if ($this->hasChanged($key, $text, $defaults)) {
                    $config['sections'][] = ['title' => $title, 'body' => (string) $text[$key]];
                }
            }
        }

        if ($page === 'methodology') {
            $config['h1'] = $this->changedValue('methodology.title', $text, $defaults, $config['h1']);
            $config['lead'] = $this->changedValue('methodology.intro', $text, $defaults, $config['lead']);

            foreach (['methodology.card_1_text', 'methodology.card_2_text', 'methodology.card_3_text'] as $index => $key) {
                $config['sections'][$index]['body'] = $this->changedValue(
                    $key,
                    $text,
                    $defaults,
                    $config['sections'][$index]['body'] ?? '',
                );
            }
        }

        if ($page === 'groups') {
            $config['lead'] = $this->changedValue('catalog.groups_intro', $text, $defaults, $config['lead']);
        }

        if ($page === 'team') {
            $config['h1'] = $this->changedValue('catalog.team_title', $text, $defaults, $config['h1']);
            $config['lead'] = $this->changedValue('catalog.team_intro', $text, $defaults, $config['lead']);
        }

        if ($page === 'gallery') {
            $config['h1'] = $this->changedValue('catalog.gallery_title', $text, $defaults, $config['h1']);
            $config['lead'] = $this->changedValue('catalog.gallery_intro', $text, $defaults, $config['lead']);
        }

        if ($page === 'blog') {
            $config['h1'] = $this->changedValue('catalog.blog_title', $text, $defaults, $config['h1']);
            $config['lead'] = $this->changedValue('catalog.blog_intro', $text, $defaults, $config['lead']);
        }

        if ($page === 'faq') {
            $config['h1'] = $this->changedValue('catalog.faq_title', $text, $defaults, $config['h1']);
            $config['lead'] = $this->changedValue('catalog.faq_intro', $text, $defaults, $config['lead']);
        }

        if ($page === 'contact') {
            $config['h1'] = $this->changedValue('contact.title', $text, $defaults, $config['h1']);
            $config['lead'] = $this->changedValue('contact.intro', $text, $defaults, $config['lead']);
            $config['sections'][0]['body'] = $this->changedValue(
                'contact.address',
                $text,
                $defaults,
                $config['sections'][0]['body'] ?? '',
            );
            $config['sections'][1]['body'] = $this->changedValue(
                'contact.phone_display',
                $text,
                $defaults,
                $config['sections'][1]['body'] ?? '',
            );
            $config['sections'][2]['body'] = $this->changedValue(
                'contact.hours',
                $text,
                $defaults,
                $config['sections'][2]['body'] ?? '',
            );
        }

        if ($page === 'admission') {
            $config['h1'] = $this->changedValue('admission.title', $text, $defaults, $config['h1']);

            if ($this->hasChanged('admission.note_title', $text, $defaults)
                || $this->hasChanged('admission.note_text', $text, $defaults)) {
                array_unshift($config['sections'], [
                    'title' => (string) ($text['admission.note_title'] ?? 'გმადლობთ ინტერესისთვის'),
                    'body' => (string) ($text['admission.note_text'] ?? ''),
                ]);
            }
        }

        return $config;
    }

    private function changedValue(string $key, array $text, array $defaults, string $fallback): string
    {
        return $this->hasChanged($key, $text, $defaults)
            ? (string) $text[$key]
            : $fallback;
    }

    private function hasChanged(string $key, array $text, array $defaults): bool
    {
        if (! array_key_exists($key, $text)) {
            return false;
        }

        return (string) $text[$key] !== (string) ($defaults[$key] ?? '');
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
