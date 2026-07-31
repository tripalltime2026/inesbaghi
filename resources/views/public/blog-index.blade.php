<!doctype html>
<html lang="ka" class="final-site">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $page['description'] }}">
    <title>{{ $page['title'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('css/seo-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
</head>
<body class="final-site seo-page-body blog-index-body">
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

<main class="seo-page-main blog-index-main">
    <nav class="seo-breadcrumb content-width" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">მთავარი</a><span aria-hidden="true">›</span><span>ბლოგი</span>
    </nav>

    <section class="blog-index-hero content-width">
        <span class="section-badge mint">ბლოგი მშობლებისთვის</span>
        <h1>სასარგებლო რჩევები ყოველდღიური მშობლობისთვის</h1>
        <p>სრული სტატიები ბავშვის ბაღთან ადაპტაციაზე, კვებაზე, თამაშით განვითარებასა და სკოლისთვის მზადებაზე.</p>
    </section>

    <section class="blog-list content-width" aria-label="ბლოგის სტატიები">
        @forelse($posts as $post)
            @php
                $wordCount = count(preg_split('/\s+/u', trim(strip_tags((string) $post->body)), -1, PREG_SPLIT_NO_EMPTY) ?: []);
                $readMinutes = max(1, (int) ceil($wordCount / 180));
            @endphp
            <article class="blog-list-card">
                <a class="blog-list-cover" href="{{ route('public.blog.show', ['slug' => $post->slug]) }}" aria-label="სრულად წაიკითხეთ: {{ $post->title }}">
                    @if($post->cover_image)
                        <img src="{{ route('content.blog-cover', $post) }}" alt="{{ $post->cover_alt ?: $post->title }}" loading="lazy" decoding="async">
                    @else
                        <img src="{{ asset('images/ines-final-hero.svg') }}" alt="{{ $post->title }}" loading="lazy" decoding="async">
                    @endif
                </a>
                <div class="blog-list-copy">
                    <div class="blog-meta">
                        @if($post->category)<span>{{ $post->category }}</span>@endif
                        <time datetime="{{ ($post->published_at ?? $post->created_at)?->toDateString() }}">{{ ($post->published_at ?? $post->created_at)?->format('d.m.Y') }}</time>
                        <span>{{ $readMinutes }} წთ</span>
                    </div>
                    <h2><a href="{{ route('public.blog.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a></h2>
                    <p>{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body), 170) }}</p>
                    <a class="blog-read-link" href="{{ route('public.blog.show', ['slug' => $post->slug]) }}">სრულად წაკითხვა <span aria-hidden="true">→</span></a>
                </div>
            </article>
        @empty
            <div class="blog-empty">
                <h2>სტატიები მალე დაემატება</h2>
                <p>მანამდე Ines AI-ს შეგიძლიათ ჰკითხოთ ბაღის პროგრამის, ჯგუფებისა და ჩარიცხვის შესახებ.</p>
                <button class="primary-button" type="button" data-ines-ai-open>ჰკითხეთ Ines AI-ს</button>
            </div>
        @endforelse
    </section>

    <section class="seo-cta content-width">
        <div>
            <span>გაცნობითი ვიზიტი</span>
            <h2>გაიცანით ინეს ბაღი ადგილზე</h2>
            <p>დატოვეთ მოთხოვნა და ადმინისტრაცია ვიზიტის დროს შეგითანხმებთ.</p>
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
</body>
</html>
