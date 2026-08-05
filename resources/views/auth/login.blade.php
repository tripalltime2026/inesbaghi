<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>შესვლა — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/credentials-auth.css') }}">
</head>
<body>
<main class="credentials-shell">
    <a class="credentials-brand" href="{{ route('home') }}"><i></i><span><strong>ინეს ბაღი</strong><small>ანგარიშის უსაფრთხო შესვლა</small></span></a>

    <section class="credentials-card">
        <span class="credentials-badge">შესვლა</span>
        <h1>კეთილი იყოს თქვენი დაბრუნება</h1>
        <p class="credentials-lead">ჩაწერეთ რეგისტრაციისას გამოყენებული ელფოსტა ან მომხმარებლის სახელი და პაროლი.</p>

        @if(session('success'))
            <div class="credentials-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="credentials-errors">
                @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
            </div>
        @endif

        <form class="credentials-form" method="post" action="{{ route('auth.credentials.login') }}">
            @csrf
            <label class="credentials-field">
                <span>ელფოსტა ან მომხმარებლის სახელი</span>
                <input name="name" value="{{ old('name') }}" required maxlength="190" autocomplete="username" autofocus placeholder="parent@example.com">
            </label>
            <label class="credentials-field">
                <span>პაროლი</span>
                <input name="password" type="password" required maxlength="128" autocomplete="current-password" placeholder="თქვენი პაროლი">
            </label>
            <label class="credentials-check"><input type="checkbox" name="remember" value="1"><span>დამიმახსოვრე ამ მოწყობილობაზე</span></label>
            <button class="credentials-submit" type="submit">შესვლა</button>
        </form>

        <div class="credentials-links">
            <a href="{{ route('auth.credentials.register.form') }}">ახალი ანგარიში შექმენით</a>
            <a href="{{ route('home') }}">საიტზე დაბრუნება</a>
        </div>
    </section>
</main>
</body>
</html>
