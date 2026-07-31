<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>რეგისტრაცია — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/credentials-auth.css') }}">
</head>
<body>
<main class="credentials-shell">
    <a class="credentials-brand" href="{{ route('home') }}"><i></i><span><strong>ინეს ბაღი</strong><small>მარტივი რეგისტრაცია</small></span></a>

    <section class="credentials-card">
        <span class="credentials-badge">ახალი ანგარიში</span>
        <h1>რეგისტრაცია ორ ნაბიჯში</h1>
        <p class="credentials-lead">ჯერ შექმენით ანგარიში სახელითა და პაროლით. შემდეგ პროფილში დაამატებთ მობილურის ნომერსა და სხვა დეტალებს.</p>

        @if($errors->any())
            <div class="credentials-errors">
                @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
            </div>
        @endif

        <form class="credentials-form" method="post" action="{{ route('auth.credentials.register') }}">
            @csrf
            <input type="hidden" name="privacy_policy_version" value="{{ $privacyVersion }}">
            <label class="credentials-field">
                <span>სახელი ან მომხმარებლის სახელი</span>
                <input name="name" value="{{ old('name') }}" required minlength="2" maxlength="80" autocomplete="username" autofocus placeholder="მაგ. ნინო ბერიძე">
            </label>
            <label class="credentials-field">
                <span>პაროლი</span>
                <input name="password" type="password" required minlength="8" maxlength="128" autocomplete="new-password" placeholder="მინიმუმ 8 სიმბოლო">
            </label>
            <label class="credentials-check">
                <input type="checkbox" name="privacy_accepted" value="1" required @checked(old('privacy_accepted'))>
                <span>გავეცანი <a href="{{ route('privacy') }}" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და <a href="{{ route('terms') }}" target="_blank" rel="noopener">სარგებლობის პირობებს</a>.</span>
            </label>
            <button class="credentials-submit" type="submit">ანგარიშის შექმნა</button>
        </form>

        <p class="credentials-note">რეგისტრაცია ავტომატურად არ ხსნის მშობელთა კლუბს. კლუბის წვდომა გაიხსნება მხოლოდ ბავშვის პროფილთან დაკავშირებისა და აქტიური ჩარიცხვის შემდეგ.</p>

        <div class="credentials-links">
            <a href="{{ route('auth.credentials.login.form') }}">უკვე გაქვთ ანგარიში?</a>
            <a href="{{ route('home') }}">საიტზე დაბრუნება</a>
        </div>
    </section>
</main>
</body>
</html>
