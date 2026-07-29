<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ადმინისტრაცია') — ინეს ბაღი</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-modules.css') }}">
    <link rel="stylesheet" href="{{ asset('css/operations.css') }}">
</head>
<body class="admin-body">
@php
    $staffHome = auth()->user()->hasRole('admin')
        ? route('admin.dashboard')
        : (auth()->user()->hasRole('finance') ? route('admin.payments.index') : route('admin.attendance.index'));
    $staffLabel = auth()->user()->hasRole('finance') ? 'ფინანსური მართვა' : (auth()->user()->hasRole('teacher') ? 'დასწრების მართვა' : 'ადმინისტრაცია');
@endphp
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand admin-brand" href="{{ $staffHome }}">
            <span class="logo">ი</span>
            <span><strong>ინეს ბაღი</strong><small>{{ $staffLabel }}</small></span>
        </a>

        <nav class="admin-nav" aria-label="საოპერაციო ნავიგაცია">
            @if(auth()->user()->hasRole('admin'))
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">მიმოხილვა</a>
                <a class="{{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}" href="{{ route('admin.admissions.index') }}">ჩარიცხვის განაცხადები</a>
                <a class="{{ request()->routeIs('admin.children.*') ? 'active' : '' }}" href="{{ route('admin.children.index') }}">ბავშვები</a>
                <a class="{{ request()->routeIs('admin.groups.*') ? 'active' : '' }}" href="{{ route('admin.groups.index') }}">ჯგუფები</a>
            @endif
            @if(auth()->user()->hasRole('admin', 'finance'))
                <a class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">გადახდები</a>
            @endif
            @if(auth()->user()->hasRole('admin', 'teacher'))
                <a class="{{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}">დასწრება</a>
            @endif
        </nav>

        <div class="admin-user">
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->phone }}</small>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-button">გასვლა</button>
            </form>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="eyebrow">საოპერაციო სისტემა</p>
                <h1>@yield('heading', 'ადმინისტრაცია')</h1>
            </div>
            <a class="secondary" href="{{ route('home') }}">საჯარო საიტი</a>
        </header>

        @if (session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="flash info">{{ session('info') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash error">
                <strong>შეამოწმეთ მონაცემები:</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
