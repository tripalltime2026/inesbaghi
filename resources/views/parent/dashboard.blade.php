<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>მშობელთა კლუბი — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/club-groups.css') }}?v=20260804a">
</head>
<body class="club-body">
<header class="club-header">
    <a class="club-brand" href="{{ route('home') }}"><span class="club-arch"><i></i></span><span><strong>ინეს ბაღი</strong><small>კერძო საბავშვო ბაღი</small></span></a>
    <nav><a href="{{ route('home') }}">მთავარი</a><a href="{{ route('public.about') }}">ჩვენ შესახებ</a><a href="{{ route('public.groups') }}">ჯგუფები</a><a href="{{ route('public.blog') }}">ბლოგი</a><a href="{{ route('public.faq') }}">კითხვა-პასუხი</a><a href="{{ route('public.contact') }}">კონტაქტი</a></nav>
    <div class="club-head-actions"><a class="head-pill butter" href="{{ route('public.admission') }}">ვიზიტი</a><span class="head-pill navy">კლუბი</span><span class="club-user"><i>{{ mb_substr(auth()->user()->name,0,1) }}</i>{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-button">გასვლა</button></form></div>
</header>

<main class="club-main">
    <section class="club-title-row">
        <div><span class="club-eyebrow">მშობელთა კლუბი</span><h1>თქვენი ჯგუფის დახურული სივრცე</h1><p>სიახლეები, ღონისძიებები და საუბრები მხოლოდ იმ მშობლებისთვის, რომელთა ბავშვებიც იმავე ასაკობრივ ჯგუფში არიან ჩარიცხული.</p></div>
        @if($clubGroups->isNotEmpty())
            <div class="role-chips">
                @foreach($clubGroups as $group)
                    <span class="role-chip mint">🌱 {{ $group->name }}</span>
                @endforeach
                <span class="role-chip butter">🔒 დახურული კლუბი</span>
            </div>
        @else
            <div class="role-chips"><span class="role-chip mint">ჯგუფი დასაზუსტებელია</span><span class="role-chip butter">🔒 დახურული კლუბი</span></div>
        @endif
    </section>

    <section class="club-group-switcher" aria-label="ბავშვის ჯგუფის არჩევა">
        <div>
            <small>აქტიური ჯგუფი</small>
            <strong>აირჩიეთ, რომელი ჯგუფის სივრცე გსურთ</strong>
        </div>
        <div class="club-group-buttons">
            @forelse($clubGroups as $group)
                <button type="button" data-club-group="{{ $group->id }}" class="{{ $loop->first ? 'active' : '' }}">
                    <span>{{ $group->name }}</span>
                    <small>{{ $group->academic_year }}</small>
                </button>
            @empty
                <span class="club-no-group">აქტიური ჩარიცხვა ვერ მოიძებნა.</span>
            @endforelse
        </div>
    </section>

    <nav class="club-tabs" aria-label="კლუბის სექციები">
        <button class="active" type="button" data-club-tab="feed">ჯგუფის ლენტა</button>
        <button type="button" data-club-tab="events">ღონისძიებები</button>
        <button type="button" data-club-tab="forum">ჯგუფის საუბარი</button>
        <button type="button" data-club-tab="polls">გამოკითხვები</button>
        <button type="button" data-club-tab="profile">პროფილი</button>
    </nav>

    <section class="club-panel active" data-club-panel="feed">
        <div class="feed-layout">
            <div class="feed-list"><div class="club-loading">ჯგუფის სიახლეები იტვირთება…</div></div>
            <aside class="club-sidebar">
                <article class="next-event"><strong>📌 შემდეგი ღონისძიება</strong><p>ინფორმაცია იტვირთება…</p></article>
                <article class="active-poll"><strong>აქტიური გამოკითხვა</strong><p>ინფორმაცია იტვირთება…</p></article>
                <article class="privacy-note"><strong>🔒 ჯგუფის კონფიდენციალურობა</strong><p>ამ ლენტას მხოლოდ იმავე ჯგუფში აქტიურად ჩარიცხული ბავშვების მშობლები ხედავენ.</p></article>
            </aside>
        </div>
    </section>

    <section class="club-panel" data-club-panel="events">
        <div class="event-list"><div class="club-loading">ჯგუფის ღონისძიებები იტვირთება…</div></div>
    </section>

    <section class="club-panel" data-club-panel="forum">
        <div class="forum-layout">
            <aside class="forum-sidebar"><div class="club-loading">ჯგუფის წევრები იტვირთება…</div></aside>
            <div class="forum-content"><div class="club-loading">ჯგუფის საუბრები იტვირთება…</div></div>
        </div>
    </section>

    <section class="club-panel" data-club-panel="polls">
        <div class="poll-list"><div class="club-loading">ჯგუფის გამოკითხვები იტვირთება…</div></div>
    </section>

    <section class="club-panel" data-club-panel="profile">
        <div class="profile-layout">
            <article class="profile-card"><h2>პროფილი</h2><dl><div><dt>სახელი</dt><dd>{{ auth()->user()->name }}</dd></div><div><dt>ტელეფონი</dt><dd>{{ auth()->user()->phone }}</dd></div><div><dt>როლი</dt><dd>მშობელი / კლუბის წევრი</dd></div></dl><form method="post" action="{{ route('logout') }}">@csrf<button class="profile-logout" type="submit">გასვლა</button></form></article>
            <article class="notification-card"><h2>შეტყობინებები</h2><label><span>SMS შეხსენება ღონისძიებებზე</span><input type="checkbox" checked></label><label><span>SMS ახალ პოსტებზე</span><input type="checkbox" checked></label><label><span>SMS გადახდის შეხსენება</span><input type="checkbox" checked></label></article>
        </div>

        @forelse($children as $child)
            @php($enrollment=$child->enrollments->first())
            @php($todayAttendance=$child->attendanceRecords->first(fn($record)=>$record->attendance_date->isToday()))
            <article class="child-record">
                <div class="child-record-head"><span>{{ mb_substr($child->first_name,0,1) }}</span><div><small>ბავშვის პროფილი</small><h2>{{ $child->first_name }} {{ $child->last_name }}</h2><p>{{ $child->birth_date?->format('d.m.Y') ?? ($child->birth_year ? 'დაბადების წელი: '.$child->birth_year : 'დაბადების თარიღი დასაზუსტებელია') }}</p></div></div>
                @if($enrollment)
                    <div class="record-metrics"><div><span>ჯგუფი</span><strong>{{ $enrollment->group?->name ?? 'დასაზუსტებელია' }}</strong><small>{{ $enrollment->group?->academic_year }}</small></div><div><span>ჩარიცხვის სტატუსი</span><strong>{{ \App\Models\Enrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</strong><small>დაწყება: {{ $enrollment->starts_on?->format('d.m.Y') ?? '—' }}</small></div><div><span>დღევანდელი დასწრება</span><strong>{{ $todayAttendance ? (\App\Models\AttendanceRecord::STATUSES[$todayAttendance->status] ?? $todayAttendance->status) : 'ჯერ არ დაფიქსირებულა' }}</strong><small>მოსვლა: {{ $todayAttendance?->checked_in_at?->format('H:i') ?? '—' }} · წასვლა: {{ $todayAttendance?->checked_out_at?->format('H:i') ?? '—' }}</small></div></div>
                    <div class="record-columns"><section><h3>ბოლო დასწრება</h3>@forelse($child->attendanceRecords->take(7) as $record)<div class="record-row"><div><strong>{{ $record->attendance_date->format('d.m.Y') }}</strong><small>{{ $record->group?->name }}</small></div><div><span>{{ \App\Models\AttendanceRecord::STATUSES[$record->status] ?? $record->status }}</span><small>{{ $record->checked_in_at?->format('H:i') ?? '—' }} — {{ $record->checked_out_at?->format('H:i') ?? '—' }}</small></div></div>@empty<p class="record-empty">დასწრების ჩანაწერი ჯერ არ არის.</p>@endforelse</section><section><h3>დარიცხვები</h3>@forelse($enrollment->payments as $payment)@php($effective=$payment->effectiveStatus())<div class="record-row"><div><strong>{{ $payment->period }}</strong><small>ვადა: {{ $payment->due_at?->format('d.m.Y') ?? '—' }}</small></div><div><span>{{ number_format($payment->outstandingAmount(),2) }} {{ $payment->currency }}</span><small>{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</small></div></div>@empty<p class="record-empty">დარიცხვა ჯერ არ არის შექმნილი.</p>@endforelse</section></div>
                @else
                    <p class="record-empty">ბავშვის პროფილი შექმნილია, თუმცა ჯგუფში ჩარიცხვა ჯერ არ არის დამატებული.</p>
                @endif
            </article>
        @empty
            <article class="empty-profile"><span>🌱</span><h2>ბავშვის პროფილი ჯერ არ არის დაკავშირებული</h2><p>რეალური ჩარიცხვის დამტკიცების შემდეგ აქ გამოჩნდება ბავშვის ჯგუფი, დასწრება და ფინანსური ინფორმაცია.</p><a href="{{ route('public.admission') }}">ვიზიტის დაგეგმვა</a></article>
        @endforelse
    </section>
</main>
<script src="{{ asset('js/portal.js') }}"></script>
<script src="{{ asset('js/cms-portal.js') }}?v=20260804b"></script>
</body>
</html>