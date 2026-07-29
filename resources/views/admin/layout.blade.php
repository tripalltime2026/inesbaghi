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
</head>
<body class="admin-body">
<header class="admin-global-header">
    <a class="admin-global-brand" href="{{ route('home') }}"><span class="admin-brand-arch"><i></i></span><span><strong>ინეს ბაღი</strong><small>კერძო საბავშვო ბაღი</small></span></a>
    <nav><a href="{{ route('home') }}#home">მთავარი</a><a href="{{ route('home') }}#about">ჩვენ შესახებ</a><a href="{{ route('home') }}#groups">ჯგუფები</a><a href="{{ route('home') }}#blog">ბლოგი</a><a href="{{ route('home') }}#faq">კითხვა-პასუხი</a><a href="{{ route('home') }}#contact">კონტაქტი</a></nav>
    <div class="admin-global-actions"><a class="header-pill butter" href="{{ route('home') }}#admission">ჩარიცხვა</a>@if(auth()->user()->hasRole('parent'))<a class="header-pill mint" href="{{ route('parent.dashboard') }}">კლუბი</a>@endif<span class="header-pill navy">{{ auth()->user()->hasRole('admin') ? 'ადმინი' : (auth()->user()->hasRole('finance') ? 'ფინანსები' : 'დასწრება') }}</span><span class="admin-profile-chip"><i>{{ mb_substr(auth()->user()->name,0,1) }}</i>{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form></div>
</header>

<main class="admin-main-shell">
    <section class="admin-title-row"><div><p class="admin-kicker">ადმინ პანელი</p><h1>@yield('heading', 'ადმინ პანელი')</h1><p class="admin-subtitle">კონტენტი, მომხმარებლები, ჩარიცხვები და გადახდები.</p></div><a class="site-return" href="{{ route('home') }}">საჯარო საიტი →</a></section>

    <nav class="admin-tabs" aria-label="ადმინისტრაციული მოდულები">
        @if(auth()->user()->hasRole('admin'))
            <a class="{{ request()->routeIs('admin.dashboard') && !request('panel') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">ანალიტიკა</a>
            <a class="{{ request()->routeIs('admin.children.*') ? 'active' : '' }}" href="{{ route('admin.children.index') }}">მომხმარებლები</a>
            <a class="{{ request()->routeIs('admin.groups.*') ? 'active' : '' }}" href="{{ route('admin.groups.index') }}">ჯგუფები</a>
            <a class="{{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}" href="{{ route('admin.admissions.index') }}">ჩარიცხვები</a>
            <a class="preview-tab {{ request('panel')==='approvals' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'approvals']) }}">დამტკიცებები</a>
            <a class="preview-tab {{ request('panel')==='news' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'news']) }}">სიახლეები</a>
            <a class="preview-tab {{ request('panel')==='blog' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'blog']) }}">ბლოგი</a>
            <a class="preview-tab {{ request('panel')==='photos' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'photos']) }}">ფოტოები</a>
            <a class="preview-tab {{ request('panel')==='events' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'events']) }}">ღონისძიებები</a>
            <a class="preview-tab {{ request('panel')==='messages' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'messages']) }}">შეტყობინებები</a>
            <a class="preview-tab {{ request('panel')==='settings' ? 'active' : '' }}" href="{{ route('admin.dashboard',['panel'=>'settings']) }}">პარამეტრები</a>
        @endif
        @if(auth()->user()->hasRole('admin','finance'))<a class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">გადახდები</a>@endif
        @if(auth()->user()->hasRole('admin','teacher'))<a class="{{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}">დასწრება</a>@endif
    </nav>

    @if (session('success'))<div class="flash success">{{ session('success') }}</div>@endif
    @if (session('info'))<div class="flash info">{{ session('info') }}</div>@endif
    @if ($errors->any())<div class="flash error"><strong>შეამოწმეთ მონაცემები:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @yield('content')
</main>
</body>
</html>
