<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>პროფილის შევსება — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/credentials-auth.css') }}">
</head>
<body>
<main class="credentials-shell">
    <a class="credentials-brand" href="{{ route('home') }}"><i></i><span><strong>ინეს ბაღი</strong><small>ანგარიშის პროფილი</small></span></a>

    <section class="credentials-card">
        <span class="credentials-badge">პროფილის დეტალები</span>
        <h1>დაასრულეთ პროფილის შევსება</h1>
        <p class="credentials-lead">მობილურის ნომერი დაგვეხმარება თქვენი ჩარიცხვის განაცხადისა და ბავშვის პროფილის ანგარიშთან სწორად დაკავშირებაში. SMS დადასტურება საჭირო არ არის.</p>

        @if(session('success'))<div class="credentials-success">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="credentials-errors">
                @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
            </div>
        @endif

        <div class="profile-username"><span>შესვლის სახელი</span><strong>{{ $user->username }}</strong></div>

        <form class="credentials-form" method="post" action="{{ route('account.profile.update') }}" style="margin-top:18px">
            @csrf
            @method('PATCH')
            <label class="credentials-field">
                <span>სახელი და გვარი</span>
                <input name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name">
            </label>
            <label class="credentials-field">
                <span>მობილურის ნომერი</span>
                <input name="phone" value="{{ old('phone', $user->phone) }}" required inputmode="tel" autocomplete="tel" placeholder="5XX XX XX XX">
            </label>
            <label class="credentials-field">
                <span>ელფოსტა — სურვილის შემთხვევაში</span>
                <input name="email" value="{{ old('email', $user->email) }}" type="email" maxlength="190" autocomplete="email" placeholder="name@example.com">
            </label>
            <button class="credentials-submit" type="submit">პროფილის შენახვა</button>
        </form>

        <div class="credentials-divider"></div>

        <h2 style="margin:0 0 12px;font:700 23px 'Noto Serif Georgian',serif">პაროლის შეცვლა</h2>
        <form class="credentials-form" method="post" action="{{ route('account.password.update') }}">
            @csrf
            @method('PATCH')
            <label class="credentials-field"><span>მიმდინარე პაროლი</span><input name="current_password" type="password" required autocomplete="current-password"></label>
            <label class="credentials-field"><span>ახალი პაროლი</span><input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
            <label class="credentials-field"><span>გაიმეორეთ ახალი პაროლი</span><input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
            <button class="credentials-submit" type="submit">პაროლის შეცვლა</button>
        </form>

        <div class="profile-actions">
            <a href="{{ route('account.status') }}">ანგარიშის სტატუსი</a>
            <a href="{{ route('home') }}">საჯარო საიტი</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form>
        </div>
    </section>
</main>
</body>
</html>
