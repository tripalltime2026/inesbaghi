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
    <link rel="stylesheet" href="{{ asset('css/account-status.css') }}">
</head>
<body class="account-status-body">
<main class="account-status-shell">
    <header class="account-status-header">
        <a class="account-status-brand" href="{{ route('home') }}"><i>ი</i><span><strong>ინეს ბაღი</strong><small style="display:block;color:#667483">ანგარიშის ცენტრი</small></span></a>
        <div class="account-status-actions">
            <a href="{{ route('account.profile') }}">პროფილი</a>
            <a href="{{ route('home') }}">საჯარო საიტი</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form>
        </div>
    </header>

    @if(session('success'))<div class="account-flash">{{ session('success') }}</div>@endif

    @php
        $approved = $user->isClubAccessApproved();
        $hasChild = $children->isNotEmpty();
        $outstanding = $user->paymentOutstanding();
    @endphp

    <section class="account-hero">
        <p class="account-kicker">{{ $user->membershipLabel() }}</p>
        <h1>გამარჯობა, {{ $user->name }}</h1>
        <p>რეგისტრაციის შემდეგ ჯგუფები და ფორუმი დახურულია. წვდომას ადმინისტრატორი დაგიდასტურებთ, ხოლო გადასახდელი თანხა ამავე გვერდზე გამოჩნდება.</p>
    </section>

    <section class="status-step-grid" aria-label="ანგარიშის სტატუსის ეტაპები">
        <article class="status-step done"><span>1</span><h2>ანგარიში</h2><p>რეგისტრაცია დასრულებულია.</p></article>
        <article class="status-step {{ $approved ? 'done' : 'waiting' }}"><span>2</span><h2>ადმინის თანხმობა</h2><p>{{ $approved ? 'წვდომა დამტკიცებულია.' : 'დადასტურებას ელოდება.' }}</p></article>
        <article class="status-step {{ $hasChild ? 'done' : 'waiting' }}"><span>3</span><h2>ბავშვი და ჯგუფი</h2><p>{{ $hasChild ? 'ბავშვი ანგარიშთან დაკავშირებულია.' : 'ადმინისტრატორი ჯერ ამუშავებს მონაცემებს.' }}</p></article>
        <article class="status-step {{ $clubAccess ? 'done' : 'waiting' }}"><span>4</span><h2>ჯგუფები და ფორუმი</h2><p>{{ $clubAccess ? 'წვდომა გახსნილია.' : 'წვდომა ჯერ დახურულია.' }}</p></article>
    </section>

    <section class="account-grid">
        <div class="account-panel">
            <h2>გადასახდელი</h2>
            <div class="account-meta">
                <div><span>სულ გადასახდელი</span><strong>{{ number_format((float)$user->payment_due, 2) }} ₾</strong></div>
                <div><span>გადახდილია</span><strong>{{ number_format((float)$user->payment_paid, 2) }} ₾</strong></div>
                <div><span>დარჩენილი</span><strong>{{ number_format($outstanding, 2) }} ₾</strong></div>
                <div><span>გადახდის ვადა</span><strong>{{ $user->payment_due_at?->format('d.m.Y') ?? 'არ არის მითითებული' }}</strong></div>
            </div>
            @if($user->payment_note)
                <div class="empty-account" style="margin-top:16px"><strong>ადმინისტრატორის შენიშვნა:</strong><br>{{ $user->payment_note }}</div>
            @endif
        </div>

        <aside class="account-panel">
            <h2>წვდომის მდგომარეობა</h2>
            <div class="account-meta">
                <div><span>ადმინის თანხმობა</span><strong>{{ $approved ? 'დადასტურებულია' : 'მოლოდინშია' }}</strong></div>
                <div><span>კლუბის წვდომა</span><strong>{{ $clubAccess ? 'გახსნილია' : 'დახურულია' }}</strong></div>
                <div><span>ბავშვის კავშირი</span><strong>{{ $hasChild ? 'დაკავშირებულია' : 'მოლოდინშია' }}</strong></div>
                <div><span>ანგარიში</span><strong>{{ $user->status === 'active' ? 'აქტიური' : $user->status }}</strong></div>
            </div>
            @if($clubAccess)
                <a class="account-cta" href="{{ route('parent.dashboard') }}">ჯგუფებსა და ფორუმზე გადასვლა →</a>
            @else
                <div class="empty-account" style="margin-top:16px">ადმინისტრატორის დადასტურებამდე ჯგუფებისა და ფორუმის გვერდები არ გაიხსნება.</div>
            @endif
        </aside>
    </section>

    <section class="account-grid">
        <div class="account-panel">
            <h2>ბავშვები და ჯგუფები</h2>
            @forelse($children as $child)
                @php($enrollment = $child->enrollments->sortByDesc('created_at')->first())
                <article class="account-record">
                    <strong>{{ $child->first_name }} {{ $child->last_name }}</strong>
                    <small>{{ $enrollment?->group?->name ?? 'ჯგუფი ჯერ არ არის მინიჭებული' }}</small>
                    @if($enrollment)
                        <span class="account-badge {{ $enrollment->status === 'active' ? 'active' : '' }}">{{ \App\Models\Enrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</span>
                    @else
                        <span class="account-badge blocked">ჩარიცხვა არ არის შექმნილი</span>
                    @endif
                </article>
            @empty
                <div class="empty-account">ბავშვის პროფილი ჯერ არ არის დაკავშირებული.</div>
            @endforelse
        </div>

        <aside class="account-panel">
            <h2>ჩარიცხვის განაცხადები</h2>
            @forelse($applications as $application)
                <article class="account-record">
                    <strong>{{ $application->child_name }}</strong>
                    <small>{{ $application->preferred_group }} · {{ $application->academic_year }}</small>
                    <span class="account-badge">{{ \App\Models\AdmissionApplication::STATUSES[$application->status] ?? $application->status }}</span>
                </article>
            @empty
                <div class="empty-account">ამ ანგარიშზე განაცხადი არ მოიძებნა.</div>
            @endforelse
        </aside>
    </section>

    <section class="account-panel" style="margin-top:20px">
        <form class="account-preferences" method="post" action="{{ route('account.preferences.update') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="marketing_consent" value="0">
            <label><input type="checkbox" name="marketing_consent" value="1" @checked($marketingConsent)><span><strong>სიახლეები და ღონისძიებები</strong><small>საინფორმაციო შეტყობინებების მიღება არჩევითია.</small></span></label>
            <button type="submit">პარამეტრის შენახვა</button>
        </form>
    </section>
</main>
</body>
</html>