<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', 'ინეს ბაღის სამართლებრივი ინფორმაცია და პერსონალური მონაცემების დაცვის წესები.')">
    <title>@yield('title') — ინეს ბაღი</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body class="legal-body">
<header class="legal-header">
    <a class="legal-brand" href="{{ route('home') }}"><span class="brand-arch"><i></i></span><span><strong>ინეს ბაღი</strong><small>სამართლებრივი ინფორმაცია</small></span></a>
    <nav aria-label="სამართლებრივი გვერდები">
        <a class="{{ request()->routeIs('privacy') ? 'active' : '' }}" href="{{ route('privacy') }}">კონფიდენციალურობა</a>
        <a class="{{ request()->routeIs('terms') ? 'active' : '' }}" href="{{ route('terms') }}">სარგებლობის პირობები</a>
        <a class="{{ request()->routeIs('privacy.request*') ? 'active' : '' }}" href="{{ route('privacy.request') }}">მონაცემთა მოთხოვნა</a>
    </nav>
    <a class="legal-home-link" href="{{ route('home') }}">საიტზე დაბრუნება →</a>
</header>

<main class="legal-shell">
    @if(session('success'))<div class="legal-flash success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="legal-flash error"><strong>შეამოწმეთ მონაცემები:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>

<footer class="legal-footer">
    <div><strong>{{ $companyName }}</strong><span>ს/კ {{ $identificationCode }} · {{ $companyAddress }}</span></div>
    <div><a href="tel:+995555411831">{{ $companyPhone }}</a><span>პოლიტიკის ვერსია: {{ $policyVersion }}</span></div>
</footer>
</body>
</html>
