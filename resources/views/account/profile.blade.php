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
        <p class="credentials-lead">პროფილში ინახება მშობლისა და მასთან დაკავშირებული ბავშვის ინფორმაცია. Parent Club-ზე წვდომისთვის ბავშვის მიბმა აუცილებელია.</p>

        @if(session('success'))<div class="credentials-success">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="credentials-errors">
                @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
            </div>
        @endif

        @if($children->isEmpty())
            <div style="margin:20px 0;padding:18px;border:1px solid #e8c872;border-radius:18px;background:#fffaf0">
                <span class="credentials-badge">სავალდებულო ნაბიჯი</span>
                <h2 style="margin:10px 0 8px;font:700 23px 'Noto Serif Georgian',serif">ბავშვის მიბმა აუცილებელია</h2>
                <p style="margin:0;line-height:1.7;color:#5e6470">თქვენს ანგარიშს ჯერ ბავშვი არ აქვს დაკავშირებული. ქვემოთ შეავსეთ ბავშვის მონაცემები — ამის გარეშე ადმინისტრატორი ვერ შეძლებს ჯგუფში ჩარიცხვას და Parent Club არ გაიხსნება.</p>
            </div>
        @else
            <div style="margin:20px 0;padding:18px;border:1px solid #dce9e1;border-radius:18px;background:#f8fcf9">
                <span class="credentials-badge">ბავშვის პროფილი</span>
                <h2 style="margin:10px 0 12px;font:700 23px 'Noto Serif Georgian',serif">დაკავშირებული ბავშვები</h2>
                @foreach($children as $child)
                    @php($enrollment = $child->enrollments->sortByDesc('created_at')->first())
                    <div style="padding:12px 0;{{ !$loop->last ? 'border-bottom:1px solid #e3ebe6;' : '' }}">
                        <strong style="display:block">{{ $child->first_name }} {{ $child->last_name }}</strong>
                        <small style="display:block;margin-top:4px;color:#667483">დაბადება: {{ $child->birth_date?->format('d.m.Y') ?? 'არ არის მითითებული' }}</small>
                        <small style="display:block;margin-top:4px;color:#667483">ჯგუფი: {{ $enrollment?->group?->name ?? 'ადმინისტრატორის დადასტურებას ელოდება' }}</small>
                    </div>
                @endforeach
            </div>
        @endif

        <form class="credentials-form" method="post" action="{{ route('account.profile.update') }}">
            @csrf
            @method('PATCH')
            <label class="credentials-field">
                <span>შესვლის სახელი</span>
                <input name="username" value="{{ old('username', $user->username) }}" required minlength="2" maxlength="80" autocomplete="username" placeholder="მაგ. ნინო ბერიძე">
            </label>
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

            @if($children->isEmpty())
                <div class="credentials-divider"></div>
                <h2 style="margin:0 0 4px;font:700 23px 'Noto Serif Georgian',serif">ბავშვის მონაცემები</h2>
                <p style="margin:0 0 8px;color:#667483;line-height:1.6">ეს სამი ველი სავალდებულოა ბავშვის ანგარიშთან დასაკავშირებლად.</p>
                <label class="credentials-field">
                    <span>ბავშვის სახელი</span>
                    <input name="child_first_name" value="{{ old('child_first_name') }}" required minlength="2" maxlength="100" autocomplete="off">
                </label>
                <label class="credentials-field">
                    <span>ბავშვის გვარი</span>
                    <input name="child_last_name" value="{{ old('child_last_name') }}" required minlength="2" maxlength="100" autocomplete="off">
                </label>
                <label class="credentials-field">
                    <span>დაბადების თარიღი</span>
                    <input name="child_birth_date" value="{{ old('child_birth_date') }}" type="date" required max="{{ now()->format('Y-m-d') }}">
                </label>
            @endif

            <button class="credentials-submit" type="submit">{{ $children->isEmpty() ? 'პროფილის შენახვა და ბავშვის მიბმა' : 'პროფილის შენახვა' }}</button>
        </form>

        <div class="credentials-divider"></div>

        <h2 style="margin:0 0 12px;font:700 23px 'Noto Serif Georgian',serif">{{ $user->password ? 'პაროლის შეცვლა' : 'პაროლის დაყენება' }}</h2>
        <form class="credentials-form" method="post" action="{{ route('account.password.update') }}">
            @csrf
            @method('PATCH')
            @if($user->password)
                <label class="credentials-field"><span>მიმდინარე პაროლი</span><input name="current_password" type="password" required autocomplete="current-password"></label>
            @endif
            <label class="credentials-field"><span>ახალი პაროლი</span><input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
            <label class="credentials-field"><span>გაიმეორეთ ახალი პაროლი</span><input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
            <button class="credentials-submit" type="submit">{{ $user->password ? 'პაროლის შეცვლა' : 'პაროლის შენახვა' }}</button>
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
