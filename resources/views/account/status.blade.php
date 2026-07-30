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
            <a href="{{ route('home') }}">საჯარო საიტი</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form>
        </div>
    </header>

    <section class="account-hero">
        <p class="account-kicker">{{ $user->membershipLabel() }}</p>
        <h1>გამარჯობა, {{ $user->name }}</h1>
        <p>აქ ჩანს რეგისტრაციის, განაცხადის, ბავშვის პროფილისა და ჩარიცხვის მდგომარეობა. მშობელთა კლუბი იხსნება მხოლოდ მაშინ, როცა ბავშვი რეალურად არის დაკავშირებული თქვენს ანგარიშთან და აქვს აქტიური ჩარიცხვა.</p>
    </section>

    @php
        $phoneVerified = $user->phone_verified_at !== null;
        $hasApplication = $applications->isNotEmpty();
        $hasChild = $children->isNotEmpty();
    @endphp
    <section class="status-step-grid" aria-label="ანგარიშის სტატუსის ეტაპები">
        <article class="status-step {{ $phoneVerified ? 'done' : 'waiting' }}"><span>1</span><h2>ანგარიში</h2><p>{{ $phoneVerified ? 'ტელეფონის ნომერი დადასტურებულია.' : 'ტელეფონის დადასტურება დარჩენილია.' }}</p></article>
        <article class="status-step {{ $hasApplication ? 'done' : 'waiting' }}"><span>2</span><h2>ჩარიცხვის განაცხადი</h2><p>{{ $hasApplication ? 'თქვენს ნომერზე განაცხადი მოიძებნა.' : 'განაცხადი ჯერ არ არის შევსებული.' }}</p></article>
        <article class="status-step {{ $hasChild ? 'done' : 'waiting' }}"><span>3</span><h2>ბავშვის კავშირი</h2><p>{{ $hasChild ? 'ბავშვის პროფილი ანგარიშთან დაკავშირებულია.' : 'ადმინისტრაციას ბავშვი ჯერ არ დაუკავშირებია.' }}</p></article>
        <article class="status-step {{ $clubAccess ? 'done' : 'waiting' }}"><span>4</span><h2>მშობელთა კლუბი</h2><p>{{ $clubAccess ? 'აქტიური ჩარიცხვა დადასტურებულია.' : 'კლუბის დაშვებისთვის საჭიროა აქტიური ჩარიცხვა.' }}</p></article>
    </section>

    <section class="account-grid">
        <div class="account-panel">
            <h2>ბავშვები და ჩარიცხვები</h2>
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
                <div class="empty-account">თქვენი ანგარიში რეგისტრირებულია, მაგრამ ბავშვის პროფილი ჯერ არ არის დაკავშირებული. ეს არ ნიშნავს, რომ თქვენ ავტომატურად ხართ ბაღის მშობელი.</div>
            @endforelse

            @if($clubAccess)
                <a class="account-cta" href="{{ route('parent.dashboard') }}">მშობელთა კლუბში გადასვლა →</a>
            @elseif(!$hasApplication)
                <a class="account-cta" href="{{ route('home') }}#admission">ჩარიცხვის განაცხადის შევსება →</a>
            @endif
        </div>

        <aside class="account-panel">
            <h2>ანგარიშის ინფორმაცია</h2>
            <div class="account-meta">
                <div><span>ტელეფონი</span><strong>{{ $user->phone }}</strong></div>
                <div><span>ანგარიშის სტატუსი</span><strong>{{ $user->status === 'active' ? 'აქტიური' : $user->status }}</strong></div>
                <div><span>როლი</span><strong>{{ $user->membershipLabel() }}</strong></div>
                <div><span>კლუბის წვდომა</span><strong>{{ $clubAccess ? 'დაშვებულია' : 'დახურულია' }}</strong></div>
            </div>

            <h2 style="margin-top:24px">განაცხადები</h2>
            @forelse($applications as $application)
                <article class="account-record">
                    <strong>{{ $application->child_name }}</strong>
                    <small>{{ $application->preferred_group }} · {{ $application->academic_year }}</small>
                    <span class="account-badge">{{ \App\Models\AdmissionApplication::STATUSES[$application->status] ?? $application->status }}</span>
                </article>
            @empty
                <div class="empty-account">ამ ტელეფონის ნომერზე ჩარიცხვის განაცხადი არ მოიძებნა.</div>
            @endforelse
        </aside>
    </section>

    @unless($clubAccess)
        <section class="account-help"><strong>რატომ არ იხსნება მშობელთა კლუბი?</strong><p>მხოლოდ რეგისტრაცია საკმარისი არ არის. ადმინისტრატორმა უნდა დააკავშიროს თქვენი ანგარიში ბავშვის პროფილთან და ჩარიცხვის სტატუსი გახდეს „აქტიური“. განაცხადის განხილვის ან pending ჩარიცხვის პერიოდში კლუბი დახურული რჩება.</p></section>
    @endunless
</main>
</body>
</html>
