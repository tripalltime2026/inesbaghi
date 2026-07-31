<!doctype html>
<html lang="ka" class="final-site">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <meta name="description" content="{{ $description }}">
    <title>{{ $post->title }} | ინეს ბაღი</title>
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="ka-GE" href="{{ $canonical }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonical }}">
    <meta property="og:locale" content="ka_GE">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="ინეს ბაღი">
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="article:published_time" content="{{ ($post->published_at ?? $post->created_at)?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at?->toIso8601String() }}">
    @if($post->category)<meta property="article:section" content="{{ $post->category }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $imageUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('css/seo-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    '@id' => $canonical.'#article',
                    'headline' => $post->title,
                    'description' => $description,
                    'url' => $canonical,
                    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
                    'image' => [$imageUrl],
                    'datePublished' => ($post->published_at ?? $post->created_at)?->toIso8601String(),
                    'dateModified' => $post->updated_at?->toIso8601String(),
                    'inLanguage' => 'ka-GE',
                    'articleSection' => $post->category,
                    'author' => ['@type' => 'Organization', 'name' => 'ინეს ბაღი', 'url' => rtrim((string) config('seo.site_url'), '/')],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'ინეს ბაღი',
                        'url' => rtrim((string) config('seo.site_url'), '/'),
                        'logo' => ['@type' => 'ImageObject', 'url' => rtrim((string) config('seo.site_url'), '/').'/images/ines-final-hero.svg'],
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'მთავარი', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'ბლოგი', 'item' => route('public.blog')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => $canonical],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</head>
<body class="final-site seo-page-body blog-article-body">
<header class="site-header seo-site-header">
    <a class="brand-lockup" href="{{ route('home') }}" aria-label="ინეს ბაღის მთავარ გვერდზე დაბრუნება">
        <span class="brand-arch"><i></i></span>
        <span><strong>ინეს ბაღი</strong><small>კერძო საბავშვო ბაღი</small></span>
    </a>
    <nav class="site-nav seo-main-nav" aria-label="მთავარი ნავიგაცია">
        <a href="{{ route('home') }}">მთავარი</a>
        <a href="{{ route('public.about') }}">ჩვენ შესახებ</a>
        <a href="{{ route('public.methodology') }}">მეთოდოლოგია</a>
        <a href="{{ route('public.groups') }}">ჯგუფები</a>
        <a class="active" href="{{ route('public.blog') }}">ბლოგი</a>
        <a href="{{ route('public.faq') }}">კითხვა-პასუხი</a>
        <a href="{{ route('public.contact') }}">კონტაქტი</a>
    </nav>
    <div class="site-actions">
        <a class="pill butter" href="{{ route('public.admission') }}">ჩარიცხვა</a>
        @auth
            @php
                $cabinetUrl = auth()->user()->hasRole('admin')
                    ? route('admin.dashboard')
                    : (auth()->user()->hasRole('finance')
                        ? route('admin.payments.index')
                        : (auth()->user()->hasRole('teacher')
                            ? route('admin.attendance.index')
                            : route('account.status')));
            @endphp
            <a class="pill navy" href="{{ $cabinetUrl }}">კაბინეტი</a>
        @else
            <a class="pill navy" href="{{ route('auth.credentials.login.form') }}">შესვლა</a>
        @endauth
    </div>
</header>

<main class="blog-article-main">
    <nav class="seo-breadcrumb content-width" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">მთავარი</a><span aria-hidden="true">›</span><a href="{{ route('public.blog') }}">ბლოგი</a><span aria-hidden="true">›</span><span>{{ $post->title }}</span>
    </nav>

    <article class="blog-article content-width">
        <header class="blog-article-header">
            <div class="blog-meta">
                @if($post->category)<span>{{ $post->category }}</span>@endif
                <time datetime="{{ ($post->published_at ?? $post->created_at)?->toDateString() }}">{{ $publishedDate }}</time>
                <span>{{ $readMinutes }} წუთი წასაკითხად</span>
            </div>
            <h1>{{ $post->title }}</h1>
            @if($post->excerpt)<p class="blog-article-lead">{{ $post->excerpt }}</p>@endif
        </header>

        <figure class="blog-article-cover">
            @if($post->cover_image)
                <img src="{{ route('content.blog-cover', $post) }}" alt="{{ $post->cover_alt ?: $post->title }}" fetchpriority="high" decoding="async">
            @else
                <img src="{{ asset('images/ines-final-hero.svg') }}" alt="{{ $post->title }}" fetchpriority="high" decoding="async">
            @endif
        </figure>

        <div class="blog-article-layout">
            <div class="blog-article-content">
                @forelse($bodyBlocks as $block)
                    <p>{!! nl2br(e($block)) !!}</p>
                @empty
                    <p>{{ $post->excerpt }}</p>
                @endforelse
            </div>

            <aside class="blog-share" aria-label="სტატიის გაზიარება">
                <strong>გააზიარეთ სტატია</strong>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonical) }}" target="_blank" rel="noopener noreferrer">Facebook</a>
                <a href="https://wa.me/?text={{ urlencode($post->title.' '.$canonical) }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                <button type="button" data-copy-article-link data-url="{{ $canonical }}">ბმულის კოპირება</button>
                <span class="copy-status" data-copy-status aria-live="polite"></span>
            </aside>
        </div>
    </article>

    @if($relatedPosts->isNotEmpty())
        <section class="related-posts content-width" aria-label="დაკავშირებული სტატიები">
            <div class="related-heading">
                <span class="section-badge butter">შემდეგი საკითხავი</span>
                <h2>სხვა სასარგებლო სტატიები</h2>
            </div>
            <div class="related-grid">
                @foreach($relatedPosts as $related)
                    <article>
                        <a class="related-cover" href="{{ route('public.blog.show', ['slug' => $related->slug]) }}">
                            @if($related->cover_image)
                                <img src="{{ route('content.blog-cover', $related) }}" alt="{{ $related->cover_alt ?: $related->title }}" loading="lazy" decoding="async">
                            @else
                                <img src="{{ asset('images/ines-final-hero.svg') }}" alt="{{ $related->title }}" loading="lazy" decoding="async">
                            @endif
                        </a>
                        <div>
                            @if($related->category)<small>{{ $related->category }}</small>@endif
                            <h3><a href="{{ route('public.blog.show', ['slug' => $related->slug]) }}">{{ $related->title }}</a></h3>
                            <a class="blog-read-link" href="{{ route('public.blog.show', ['slug' => $related->slug]) }}">წაკითხვა →</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="seo-cta content-width">
        <div>
            <span>ინეს ბაღი</span>
            <h2>გსურთ გარემოსა და პროგრამის ადგილზე გაცნობა?</h2>
            <p>დატოვეთ ვიზიტის მოთხოვნა და ადმინისტრაცია დაგიკავშირდებათ.</p>
        </div>
        <a class="primary-button" href="{{ route('public.admission') }}">ვიზიტის დაგეგმვა</a>
    </section>
</main>

<footer class="site-footer">
    <div class="content-width footer-row"><span>© 2026 ინეს ბაღი · ლერმონტოვის 53, ბათუმი</span><a href="tel:+995555411831">+995 555 41 18 31</a></div>
    <nav class="seo-footer-nav" aria-label="საჯარო გვერდები">
        <a href="{{ route('public.about') }}">ჩვენ შესახებ</a>
        <a href="{{ route('public.methodology') }}">მეთოდოლოგია</a>
        <a href="{{ route('public.groups') }}">ჯგუფები</a>
        <a href="{{ route('public.blog') }}">ბლოგი</a>
        <a href="{{ route('public.faq') }}">კითხვა-პასუხი</a>
        <a href="{{ route('public.contact') }}">კონტაქტი</a>
        <a href="{{ route('public.admission') }}">ჩარიცხვა</a>
    </nav>
</footer>
<script>
(() => {
    const button = document.querySelector('[data-copy-article-link]');
    if (!button) return;
    button.addEventListener('click', async () => {
        const status = document.querySelector('[data-copy-status]');
        try {
            await navigator.clipboard.writeText(button.dataset.url || window.location.href);
            if (status) status.textContent = 'ბმული დაკოპირდა';
        } catch (error) {
            if (status) status.textContent = 'ბმულის კოპირება ვერ მოხერხდა';
        }
    });
})();
</script>
</body>
</html>
