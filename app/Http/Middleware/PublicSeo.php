<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicSeo
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, '</head>')) {
            return $response;
        }

        $page = $this->resolvePage((string) optional($request->route())->getName());
        if ($page === null) {
            return $response;
        }

        $baseUrl = rtrim((string) config('seo.site_url'), '/');
        $canonical = $baseUrl.$page['path'];
        $image = $baseUrl.(string) config('seo.image');
        $title = $this->escape((string) $page['title']);
        $description = $this->escape((string) $page['description']);

        $content = preg_replace('/<title>.*?<\/title>/si', "<title>{$title}</title>", $content, 1) ?? $content;
        $content = preg_replace(
            '/<meta\s+name=["\']description["\'][^>]*>/i',
            '<meta name="description" content="'.$description.'">',
            $content,
            1,
        ) ?? $content;

        if (! str_contains($content, 'data-public-seo')) {
            $head = $this->headMarkup($page, $canonical, $image);
            $content = str_replace('</head>', $head."\n</head>", $content);
        }

        if (str_contains($content, 'class="site-footer"') && ! str_contains($content, 'aria-label="საჯარო გვერდები"')) {
            $content = preg_replace_callback(
                '/(<footer class="site-footer">.*?)(<\/footer>)/s',
                fn (array $matches): string => $matches[1].$this->footerNavigation().$matches[2],
                $content,
                1,
            ) ?? $content;
        }

        $content = str_replace(
            '<img src="'.asset('images/ines-final-hero.svg').'" alt="ინეს ბაღი — სივრცე ბავშვებისთვის">',
            '<img src="'.asset('images/ines-final-hero.svg').'" alt="ინეს ბაღი — კერძო საბავშვო ბაღი ბათუმში" width="1080" height="1080" decoding="async" fetchpriority="high">',
            $content,
        );

        $response->setContent($content);
        $response->headers->remove('Content-Length');
        $response->headers->set('X-Robots-Tag', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');

        return $response;
    }

    private function resolvePage(string $routeName): ?array
    {
        foreach (config('seo.pages', []) as $page) {
            if (($page['route'] ?? null) === $routeName) {
                return $page;
            }
        }

        return null;
    }

    private function headMarkup(array $page, string $canonical, string $image): string
    {
        $title = $this->escape((string) $page['title']);
        $description = $this->escape((string) $page['description']);
        $siteName = $this->escape((string) config('seo.site_name'));
        $locale = $this->escape((string) config('seo.locale'));
        $language = $this->escape((string) config('seo.language'));
        $schema = json_encode(
            $this->schemaGraph($page, $canonical, $image),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return <<<HTML
    <!-- Page-specific technical SEO -->
    <meta data-public-seo name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <link rel="canonical" href="{$canonical}">
    <link rel="alternate" hreflang="{$language}" href="{$canonical}">
    <link rel="alternate" hreflang="x-default" href="{$canonical}">
    <link rel="sitemap" type="application/xml" href="/sitemap.xml">
    <link rel="stylesheet" href="/css/seo-pages.css?v=20260730">
    <meta property="og:locale" content="{$locale}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{$siteName}">
    <meta property="og:title" content="{$title}">
    <meta property="og:description" content="{$description}">
    <meta property="og:url" content="{$canonical}">
    <meta property="og:image" content="{$image}">
    <meta property="og:image:alt" content="ინეს ბაღი — ბავშვზე ორიენტირებული სივრცე ბათუმში">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$title}">
    <meta name="twitter:description" content="{$description}">
    <meta name="twitter:image" content="{$image}">
    <meta name="geo.region" content="GE-AJ">
    <meta name="geo.placename" content="ბათუმი">
    <script type="application/ld+json">{$schema}</script>
HTML;
    }

    private function schemaGraph(array $page, string $canonical, string $image): array
    {
        $baseUrl = rtrim((string) config('seo.site_url'), '/');
        $pageType = (string) ($page['schema_type'] ?? 'WebPage');
        $graph = [
            [
                '@type' => 'WebSite',
                '@id' => $baseUrl.'/#website',
                'url' => $baseUrl.'/',
                'name' => config('seo.site_name'),
                'alternateName' => config('seo.alternate_name'),
                'inLanguage' => config('seo.language'),
            ],
            [
                '@type' => ['Preschool', 'LocalBusiness'],
                '@id' => $baseUrl.'/#preschool',
                'name' => config('seo.site_name'),
                'alternateName' => config('seo.alternate_name'),
                'url' => $baseUrl.'/',
                'image' => $image,
                'telephone' => config('seo.telephone'),
                'slogan' => 'სიყვარულით',
                'foundingDate' => '2022',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => config('seo.address.street'),
                    'addressLocality' => config('seo.address.locality'),
                    'addressRegion' => config('seo.address.region'),
                    'addressCountry' => config('seo.address.country'),
                ],
                'openingHoursSpecification' => [[
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'opens' => '08:00',
                    'closes' => '19:00',
                ]],
                'areaServed' => [
                    '@type' => 'City',
                    'name' => 'ბათუმი',
                ],
                'knowsAbout' => [
                    'ბავშვზე ორიენტირებული სწავლება',
                    'მონტესორის ელემენტები',
                    'თამაშზე დაფუძნებული სწავლება',
                    'სკოლამდელი განათლება',
                ],
            ],
            [
                '@type' => $pageType,
                '@id' => $canonical.'#webpage',
                'url' => $canonical,
                'name' => $page['title'],
                'description' => $page['description'],
                'inLanguage' => config('seo.language'),
                'isPartOf' => ['@id' => $baseUrl.'/#website'],
                'about' => ['@id' => $baseUrl.'/#preschool'],
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $image,
                ],
            ],
        ];

        if ($page['path'] !== '/') {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $canonical.'#breadcrumb',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'მთავარი',
                        'item' => $baseUrl.'/',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $page['eyebrow'] ?? $page['title'],
                        'item' => $canonical,
                    ],
                ],
            ];
        }

        if ($pageType === 'FAQPage' && ! empty($page['faqs'])) {
            $graph[2]['mainEntity'] = collect($page['faqs'])->map(fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ])->values()->all();
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    private function footerNavigation(): string
    {
        $links = collect(config('seo.pages'))
            ->reject(fn (array $page, string $key): bool => $key === 'home')
            ->map(fn (array $page): string => sprintf(
                '<a href="%s">%s</a>',
                $this->escape((string) $page['path']),
                $this->escape((string) ($page['eyebrow'] ?? $page['title'])),
            ))
            ->implode('');

        return '<nav class="seo-footer-nav" aria-label="საჯარო გვერდები">'.$links.'</nav>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
