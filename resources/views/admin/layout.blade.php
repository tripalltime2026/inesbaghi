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
</head>
<body class="admin-body">
<header class="admin-global-header">
    <a class="admin-global-brand" href="{{ route('admin.dashboard') }}"><span class="admin-brand-arch"><i></i></span><span><strong>ინეს ბაღი</strong><small>მართვის ცენტრი</small></span></a>
    <nav><a href="{{ route('home') }}#home">მთავარი</a><a href="{{ route('home') }}#about">ჩვენ შესახებ</a><a href="{{ route('home') }}#groups">ჯგუფები</a><a href="{{ route('home') }}#blog">ბლოგი</a><a href="{{ route('home') }}#contact">კონტაქტი</a></nav>
    <div class="admin-global-actions"><a class="header-pill butter" href="{{ route('home') }}#admission">ჩარიცხვა</a><span class="header-pill navy">{{ auth()->user()->hasRole('admin') ? 'ადმინი' : (auth()->user()->hasRole('finance') ? 'ფინანსები' : 'დასწრება') }}</span><span class="admin-profile-chip"><i>{{ mb_substr(auth()->user()->name,0,1) }}</i>{{ auth()->user()->name }}</span></div>
</header>

<div class="admin-mobile-overlay" aria-hidden="true"></div>
<main class="admin-main-shell">
    <aside class="admin-sidebar" aria-label="ადმინისტრატორის მენიუ">
        <div class="admin-sidebar-head"><div><strong>მართვის ცენტრი</strong><small>სწრაფი წვდომა ყველა მოდულზე</small></div><button class="admin-sidebar-close" type="button" data-admin-sidebar-close aria-label="მენიუს დახურვა">×</button></div>
        <label class="admin-nav-search"><span>⌕</span><input type="search" data-admin-nav-search placeholder="მენიუში ძებნა" aria-label="მენიუში ძებნა"></label>
        <nav class="admin-tabs admin-nav-stack" aria-label="ადმინისტრაციული მოდულები">
            @if(auth()->user()->hasRole('admin'))
                <span class="admin-nav-group-label">მთავარი მართვა</span>
                <a class="{{ request()->routeIs('admin.dashboard') && !request('panel') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="admin-nav-icon">⌂</span>მიმოხილვა</a>
                <a class="{{ request()->routeIs('admin.content.*') ? 'active' : '' }}" href="{{ route('admin.content.index') }}"><span class="admin-nav-icon">✦</span>პლატფორმის მართვა</a>
                <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span class="admin-nav-icon">◎</span>მომხმარებელთა რეესტრი</a>
                <a class="{{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}" href="{{ route('admin.admissions.index') }}"><span class="admin-nav-icon">＋</span>ჩარიცხვები</a>
                <a class="{{ request()->routeIs('admin.children.*') ? 'active' : '' }}" href="{{ route('admin.children.index') }}"><span class="admin-nav-icon">●</span>ბავშვები და მშობლები</a>
                <a class="{{ request()->routeIs('admin.groups.*') ? 'active' : '' }}" href="{{ route('admin.groups.index') }}"><span class="admin-nav-icon">◫</span>ჯგუფები</a>
                <a class="{{ request('panel')==='approvals' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'approvals']) }}"><span class="admin-nav-icon">✓</span>დამტკიცებები</a>
                <a class="{{ request()->routeIs('admin.privacy.*') ? 'active' : '' }}" href="{{ route('admin.privacy.index') }}"><span class="admin-nav-icon">◈</span>მონაცემთა დაცვა</a>

                <span class="admin-nav-group-label">კონტენტი და კომუნიკაცია</span>
                <a href="{{ route('admin.content.index') }}#cms-blog"><span class="admin-nav-icon">▤</span>ბლოგი და ქავერები</a>
                <a href="{{ route('admin.content.index') }}#cms-gallery"><span class="admin-nav-icon">▧</span>გალერეა</a>
                <a href="{{ route('admin.content.index') }}#cms-club_post"><span class="admin-nav-icon">◌</span>კლუბის ლენტა</a>
                <a href="{{ route('admin.content.index') }}#cms-club_event"><span class="admin-nav-icon">◷</span>ღონისძიებები</a>
                <a href="{{ route('admin.content.index') }}#cms-club_poll"><span class="admin-nav-icon">▥</span>გამოკითხვები</a>
            @endif

            @if(auth()->user()->hasRole('admin','finance'))
                <span class="admin-nav-group-label">ოპერაციები</span>
                <a class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><span class="admin-nav-icon">₾</span>გადახდები</a>
            @endif
            @if(auth()->user()->hasRole('admin','teacher'))
                <a class="{{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}"><span class="admin-nav-icon">◉</span>დასწრება</a>
            @endif
        </nav>
        <div class="admin-sidebar-footer"><a href="{{ route('privacy') }}" target="_blank" rel="noopener">კონფიდენციალურობა ↗</a><a href="{{ route('home') }}" target="_blank" rel="noopener">საჯარო საიტის ნახვა ↗</a><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form></div>
    </aside>

    <section class="admin-workspace">
        <section class="admin-title-row">
            <button class="admin-mobile-menu" type="button" data-admin-sidebar-open aria-label="მართვის მენიუს გახსნა">☰</button>
            <div><p class="admin-kicker">მართვის ცენტრი</p><h1>@yield('heading', 'ადმინ პანელი')</h1><p class="admin-subtitle">კონტენტი, მომხმარებლები, ჩარიცხვები, ფინანსები და ყოველდღიური ოპერაციები.</p></div>
            <div class="admin-title-actions"><a class="site-return" href="{{ route('home') }}">საჯარო საიტი →</a></div>
        </section>

        @if (session('success'))<div class="flash success">{{ session('success') }}</div>@endif
        @if (session('info'))<div class="flash info">{{ session('info') }}</div>@endif
        @if ($errors->any())<div class="flash error"><strong>შეამოწმეთ მონაცემები:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        @yield('content')
    </section>
</main>
</body>
</html>
