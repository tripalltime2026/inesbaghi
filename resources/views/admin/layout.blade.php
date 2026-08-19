<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ადმინ პანელი') — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-modules.css') }}">
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cms-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/support-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-simple.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-club.css') }}?v=20260805">
    <link rel="stylesheet" href="{{ asset('css/group-club-feed.css') }}?v=20260805">
    @if(request()->routeIs('admin.users.*'))<link rel="stylesheet" href="{{ asset('css/user-registry.css') }}?v=20260805">@endif
</head>
<body class="admin-body">
<header class="admin-global-header">
    <a class="admin-global-brand" href="{{ route('admin.dashboard') }}"><span class="admin-brand-arch"><i></i></span><span><strong>ინეს ბაღი</strong><small>მარტივი მართვა</small></span></a>
    <nav><a href="{{ route('home') }}#home">მთავარი</a><a href="{{ route('home') }}#about">ჩვენ შესახებ</a><a href="{{ route('home') }}#groups">ჯგუფები</a><a href="{{ route('home') }}#blog">ბლოგი</a><a href="{{ route('home') }}#contact">კონტაქტი</a></nav>
    <div class="admin-global-actions"><a class="header-pill butter" href="{{ route('home') }}#admission">ჩარიცხვა</a><span class="header-pill navy">{{ auth()->user()->hasRole('admin') ? 'ადმინი' : (auth()->user()->hasRole('finance') ? 'ფინანსები' : 'დასწრება') }}</span><span class="admin-profile-chip"><i>{{ mb_substr(auth()->user()->name,0,1) }}</i>{{ auth()->user()->name }}</span></div>
</header>

<div class="admin-mobile-overlay" aria-hidden="true"></div>
<main class="admin-main-shell">
    <aside class="admin-sidebar" aria-label="ადმინისტრატორის მენიუ">
        <div class="admin-sidebar-head"><div><strong>მართვის ცენტრი</strong><small>მხოლოდ ყოველდღიური საჭიროებები</small></div><button class="admin-sidebar-close" type="button" data-admin-sidebar-close aria-label="მენიუს დახურვა">×</button></div>
        <nav class="admin-tabs admin-nav-stack" aria-label="ადმინისტრაციული მოდულები">
            @if(auth()->user()->hasRole('admin'))
                @php($waitingSupportCount = \App\Models\SupportConversation::query()->where('status', 'waiting_admin')->count())
                @php($unansweredClubCount = \App\Models\ForumTopic::query()->where('status', 'open')->count())
                <a class="{{ request()->routeIs('admin.users.*') || request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="admin-nav-icon">◎</span>მომხმარებელთა ბაზა</a>
                <a class="{{ request()->routeIs('admin.club.index', 'admin.club.events.*', 'admin.club.topics.*') ? 'active' : '' }}" href="{{ route('admin.club.index') }}"><span class="admin-nav-icon">◌</span>მშობელთა კლუბი @if($unansweredClubCount)<small>{{ $unansweredClubCount }}</small>@endif</a>
                <a class="{{ request()->routeIs('admin.club.polls.*') ? 'active' : '' }}" href="{{ route('admin.club.polls.index') }}"><span class="admin-nav-icon">▥</span>ჯგუფის გამოკითხვები</a>
                <a class="{{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}" href="{{ route('admin.admissions.index') }}"><span class="admin-nav-icon">＋</span>ჩარიცხვის განაცხადები</a>
                <a class="{{ request()->routeIs('admin.children.*') ? 'active' : '' }}" href="{{ route('admin.children.index') }}"><span class="admin-nav-icon">●</span>ბავშვები</a>
                <a class="{{ request()->routeIs('admin.content.*') ? 'active' : '' }}" href="{{ route('admin.content.index') }}"><span class="admin-nav-icon">✦</span>საიტის კონტენტი</a>
                <a class="{{ request()->routeIs('admin.support.*') ? 'active' : '' }}" href="{{ route('admin.support.index') }}"><span class="admin-nav-icon">◇</span>მხარდაჭერა @if($waitingSupportCount)<small>{{ $waitingSupportCount }}</small>@endif</a>
            @elseif(auth()->user()->hasRole('finance'))
                <a class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><span class="admin-nav-icon">₾</span>გადახდები</a>
            @endif

            @if(auth()->user()->hasRole('admin','teacher'))
                <a class="{{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}"><span class="admin-nav-icon">◉</span>დასწრება</a>
            @endif
        </nav>
        <div class="admin-sidebar-footer"><a href="{{ route('privacy') }}" target="_blank" rel="noopener">მონაცემთა დაცვა ↗</a><a href="{{ route('home') }}" target="_blank" rel="noopener">საჯარო საიტი ↗</a><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form></div>
    </aside>

    <section class="admin-workspace">
        <section class="admin-title-row">
            <button class="admin-mobile-menu" type="button" data-admin-sidebar-open aria-label="მართვის მენიუს გახსნა">☰</button>
            <div><p class="admin-kicker">ინეს ბაღი</p><h1>@yield('heading', 'ადმინ პანელი')</h1><p class="admin-subtitle">მშობლები, ბავშვები, კითხვები, ღონისძიებები და ყოველდღიური მართვა ერთ სივრცეში.</p></div>
            <div class="admin-title-actions"><a class="site-return" href="{{ route('home') }}">საჯარო საიტი →</a></div>
        </section>

        @if (session('success'))<div class="flash success">{{ session('success') }}</div>@endif
        @if (session('info'))<div class="flash info">{{ session('info') }}</div>@endif
        @if ($errors->any())<div class="flash error"><strong>შეამოწმეთ მონაცემები:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        @yield('content')
    </section>
</main>
@if(request()->routeIs('admin.content.*'))
<script>window.__oldBlogArticleUrl = @json(old('article_url', ''));</script>
<script src="{{ asset('js/blog-import.js') }}" defer></script>
@endif
@if(request()->routeIs('admin.users.*'))
<script src="{{ asset('js/user-registry.js') }}?v=20260819b" defer></script>
@endif
</body>
</html>