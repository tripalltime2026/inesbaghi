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
    @if($pageKey === 'admission')
        <link rel="stylesheet" href="{{ asset('css/admission-form.css') }}">
    @endif
</head>
<body class="final-site seo-page-body">
<header class="site-header seo-site-header">
    <a class="brand-lockup" href="{{ route('home') }}" aria-label="ინეს ბაღის მთავარ გვერდზე დაბრუნება">
        <span class="brand-arch"><i></i></span>
        <span><strong>ინეს ბაღი</strong><small>კერძო საბავშვო ბაღი</small></span>
    </a>
    <nav class="site-nav seo-main-nav" aria-label="მთავარი ნავიგაცია">
        <a href="{{ route('home') }}">მთავარი</a>
        <a class="{{ $pageKey === 'about' ? 'active' : '' }}" href="{{ route('public.about') }}">ჩვენ შესახებ</a>
        <a class="{{ $pageKey === 'methodology' ? 'active' : '' }}" href="{{ route('public.methodology') }}">მეთოდოლოგია</a>
        <a class="{{ $pageKey === 'groups' ? 'active' : '' }}" href="{{ route('public.groups') }}">ჯგუფები</a>
        <a class="{{ $pageKey === 'blog' ? 'active' : '' }}" href="{{ route('public.blog') }}">ბლოგი</a>
        <a class="{{ $pageKey === 'faq' ? 'active' : '' }}" href="{{ route('public.faq') }}">კითხვა-პასუხი</a>
        <a class="{{ $pageKey === 'contact' ? 'active' : '' }}" href="{{ route('public.contact') }}">კონტაქტი</a>
    </nav>
    <div class="site-actions">
        <a class="pill butter" href="{{ route('public.admission') }}">ვიზიტი</a>
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

<main class="seo-page-main">
    <nav class="seo-breadcrumb content-width" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">მთავარი</a><span aria-hidden="true">›</span><span>{{ $page['eyebrow'] }}</span>
    </nav>

    <section class="seo-hero content-width">
        <div class="seo-hero-copy">
            <span class="section-badge mint">{{ $page['eyebrow'] }}</span>
            <h1>{{ $page['h1'] }}</h1>
            <p>{{ $page['lead'] }}</p>
            <div class="button-row">
                @if($pageKey === 'admission')
                    <a class="primary-button" href="#admissionFormTitle">ვიზიტის მოთხოვნა</a>
                @else
                    <a class="primary-button" href="{{ route('public.admission') }}">ვიზიტის დაგეგმვა</a>
                @endif
                <button class="secondary-button lavender" type="button" data-ines-ai-open>ჰკითხეთ Ines AI-ს</button>
            </div>
        </div>
        <div class="seo-hero-art">
            <img src="{{ asset('images/ines-final-hero.svg') }}" alt="ინეს ბაღი — კერძო საბავშვო ბაღი ბათუმში" width="1080" height="1080" decoding="async">
        </div>
    </section>

    @if($pageKey === 'admission')
        @include('public.partials.admission-form')
    @endif

    @if(!empty($page['sections']))
        <section class="seo-content-grid content-width" aria-label="{{ $page['eyebrow'] }} — დეტალური ინფორმაცია">
            @foreach($page['sections'] as $index => $section)
                <article class="seo-info-card {{ ['mint', 'butter', 'lavender', 'peach'][$index % 4] }}">
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <h2>{{ $section['title'] }}</h2>
                    <p>{{ $section['body'] }}</p>
                </article>
            @endforeach
        </section>
    @endif

    @if(!empty($page['faqs']))
        <section class="seo-faq content-width">
            @foreach($page['faqs'] as $faq)
                <details>
                    <summary>{{ $faq['question'] }}</summary>
                    <p>{{ $faq['answer'] }}</p>
                </details>
            @endforeach
        </section>
    @endif

    <section class="seo-cta content-width">
        <div>
            <span>გაცნობითი ვიზიტი</span>
            <h2>გაიცანით გარემო და პროგრამა ადგილზე</h2>
            <p>დატოვეთ მოთხოვნა და ადმინისტრაცია დაგიკავშირდებათ თქვენთვის მოსახერხებელი დროის შესათანხმებლად.</p>
        </div>
        <a class="primary-button" href="{{ $pageKey === 'admission' ? '#admissionFormTitle' : route('public.admission') }}">ვიზიტის დაგეგმვა</a>
    </section>
</main>

<footer class="site-footer">
    <div class="content-width footer-row"><span>© 2026 ინეს ბაღი · ლერმონტოვის 53, ბათუმი</span><a href="tel:+995555411831">+995 555 41 18 31</a></div>
</footer>
</body>
</html>