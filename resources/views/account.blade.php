<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ანგარიშის სტატუსი — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/account.css') }}">
</head>
<body class="account-body">
<header class="account-header">
    <a class="account-brand" href="{{ route('home') }}"><span class="account-brand-mark">ინ</span><span><strong>ინეს ბაღი</strong><br><small>ანგარიშის მართვა</small></span></a>
    <nav>
        <a href="{{ route('home') }}#home">მთავარი</a>
        <a href="{{ route('home') }}#admission">ჩარიცხვა</a>
        @if($parentAccess)<a href="{{ route('parent.dashboard') }}">მშობელთა კლუბი</a>@endif
        <form class="logout-form" method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form>
    </nav>
</header>

<main class="account-shell">
    @if(session('success'))<div class="account-flash">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="account-flash info">{{ session('info') }}</div>@endif

    <section class="account-hero">
        <article class="account-panel">
            <p class="account-kicker">ანგარიშის სტატუსი</p>
            <h1>{{ $user->name }}</h1>
            <p class="account-lead">თქვენი ტელეფონის ნომერი რეგისტრირებულია. მშობელთა კლუბის წვდომა ანგარიშის რეგისტრაციისგან ცალკე კონტროლდება და აქტიურდება მხოლოდ ბავშვის ბაღში აქტიური ჩარიცხვის შემდეგ.</p>
            @if($parentAccess)
                <span class="access-badge allowed">✓ დადასტურებული მშობელი · კლუბი ხელმისაწვდომია</span>
            @else
                <span class="access-badge pending">⌛ რეგისტრირებული ანგარიში · კლუბზე წვდომის გარეშე</span>
            @endif
            <div class="account-actions">
                @if($parentAccess)
                    <a class="account-button primary" href="{{ route('parent.dashboard') }}">კლუბში გადასვლა →</a>
                @else
                    <a class="account-button primary" href="{{ route('home') }}#admission">ჩარიცხვის განაცხადი</a>
                @endif
                <a class="account-button secondary" href="{{ route('privacy') }}">კონფიდენციალურობა</a>
            </div>
        </article>

        <aside class="account-panel">
            <p class="account-kicker">ანგარიში</p>
            <h2>ძირითადი ინფორმაცია</h2>
            <div class="account-facts">
                <div class="account-fact"><span>ტელეფონი</span><strong>{{ $user->phone }}</strong></div>
                <div class="account-fact"><span>ტელეფონი დადასტურებულია</span><strong>{{ $user->phone_verified_at ? 'დიახ' : 'არა' }}</strong></div>
                <div class="account-fact"><span>კონფიდენციალურობის მოქმედი ვერსია</span><strong>{{ $privacyAcknowledged ? 'დადასტურებულია' : 'საჭიროა დადასტურება' }}</strong></div>
                <div class="account-fact"><span>რეგისტრაციის თარიღი</span><strong>{{ $user->created_at?->format('d.m.Y') }}</strong></div>
            </div>
        </aside>
    </section>

    <section class="account-grid">
        <article class="account-panel">
            <p class="account-kicker">ბავშვები და ჩარიცხვები</p>
            <h2>დაკავშირებული პროფილები</h2>
            <div class="account-list">
                @forelse($user->children as $child)
                    @php($latestEnrollment = $child->enrollments->sortByDesc('created_at')->first())
                    <div class="account-child">
                        <strong>{{ $child->first_name }} {{ $child->last_name }}</strong>
                        <small>ჯგუფი: {{ $latestEnrollment?->group?->name ?? 'ჯერ არ არის მინიჭებული' }}</small>
                        <small>ჩარიცხვის სტატუსი: {{ \App\Models\Enrollment::STATUSES[$latestEnrollment?->status] ?? 'ჩარიცხვა არ არის' }}</small>
                    </div>
                @empty
                    <div class="account-note warning">ანგარიშთან ბავშვი ჯერ დაკავშირებული არ არის. განაცხადის განხილვისა და ადმინისტრაციის დადასტურების შემდეგ ბავშვის პროფილი აქ გამოჩნდება.</div>
                @endforelse
            </div>
            @unless($parentAccess)
                <div class="account-note warning">მხოლოდ განაცხადის შევსება ან ანგარიშის შექმნა კლუბის წვდომას არ იძლევა. წვდომა გაიხსნება, როდესაც ადმინისტრაცია ბავშვს თქვენს ანგარიშს დაუკავშირებს და ჩარიცხვას „აქტიურ“ სტატუსზე გადაიყვანს.</div>
            @endunless
        </article>

        <article class="account-panel">
            <p class="account-kicker">შეტყობინებების პარამეტრები</p>
            <h2>მარკეტინგული თანხმობა</h2>
            <p class="account-lead">ეს არჩევითი პარამეტრია და ანგარიშში შესვლას ან ბაღის ძირითად მომსახურებას არ ზღუდავს.</p>
            <form method="post" action="{{ route('account.marketing') }}">
                @csrf
                @method('patch')
                <input type="hidden" name="marketing_consent" value="0">
                <label class="preference-row">
                    <input type="checkbox" name="marketing_consent" value="1" @checked($marketingEnabled)>
                    <span><strong>მსურს მივიღო ბაღის სიახლეები და ღონისძიებების ინფორმაცია</strong><br><small>თანხმობის შეცვლა ნებისმიერ დროს შეგიძლიათ.</small></span>
                </label>
                <button class="account-button primary" type="submit">პარამეტრის შენახვა</button>
            </form>
        </article>
    </section>
</main>
<footer class="account-footer">© 2026 შპს ინეს ბაღი · ს/კ 445602465 · ქ. ბათუმი, ლერმონტოვის ქ. 53</footer>
</body>
</html>
