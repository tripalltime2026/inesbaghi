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
</head>
<body class="club-body">
<header class="club-header">
    <a class="club-brand" href="{{ route('home') }}"><span class="club-arch"><i></i></span><span><strong>ინეს ბაღი</strong><small>კერძო საბავშვო ბაღი</small></span></a>
    <nav><a href="{{ route('home') }}#home">მთავარი</a><a href="{{ route('home') }}#about">ჩვენ შესახებ</a><a href="{{ route('home') }}#groups">ჯგუფები</a><a href="{{ route('home') }}#blog">ბლოგი</a><a href="{{ route('home') }}#faq">კითხვა-პასუხი</a><a href="{{ route('home') }}#contact">კონტაქტი</a></nav>
    <div class="club-head-actions"><a class="head-pill butter" href="{{ route('home') }}#admission">ჩარიცხვა</a><span class="head-pill navy">კლუბი</span><span class="club-user"><i>{{ mb_substr(auth()->user()->name,0,1) }}</i>{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-button">გასვლა</button></form></div>
</header>

<main class="club-main">
    <section class="club-title-row">
        <div><span class="club-eyebrow">მშობელთა კლუბი</span><h1>მშობელთა კლუბი</h1><p>სიახლეები, ღონისძიებები, ფორუმი და გამოკითხვები.</p></div>
        @if($children->isNotEmpty())
            @php($firstChild=$children->first())
            @php($firstEnrollment=$firstChild->enrollments->first())
            <div class="role-chips"><span class="role-chip mint">🌱 ბაღის მშობელი · {{ $firstEnrollment?->group?->name ?? 'ჯგუფი დასაზუსტებელია' }}</span><span class="role-chip butter">💛 კლუბის წევრი</span></div>
        @else
            <div class="role-chips"><span class="role-chip mint">🌱 მშობლის დემო ანგარიში</span><span class="role-chip butter">💛 კლუბის წევრი</span></div>
        @endif
    </section>

    <nav class="club-tabs" aria-label="კლუბის სექციები">
        <button class="active" type="button" data-club-tab="feed">ლენტა</button>
        <button type="button" data-club-tab="events">ღონისძიებები</button>
        <button type="button" data-club-tab="forum">ფორუმი</button>
        <button type="button" data-club-tab="polls">გამოკითხვები</button>
        <button type="button" data-club-tab="profile">პროფილი</button>
    </nav>

    <section class="club-panel active" data-club-panel="feed">
        <div class="feed-layout">
            <div class="feed-list">
                <article class="club-post"><div class="post-art mint">ბაღის ეზოში</div><div class="post-body"><div class="post-meta"><span class="post-avatar mint">ი</span><div><strong>ინეს ბაღი</strong><small>დღეს · 09:30</small></div><span class="visibility-chip mint">კლუბის წევრები</span></div><h2>საზაფხულო ზეიმის დეტალები</h2><p>ხუთშაბათს, 20 ივლისს, ეზოში გაიმართება საზაფხულო ზეიმი. მოსვლის დრო — 17:00. ველოდებით ყველას!</p><div class="post-actions"><span>❤️ 24</span><span>💬 8 კომენტარი</span></div></div></article>
                <article class="club-post"><div class="post-art butter">თამაშები ჯგუფში</div><div class="post-body"><div class="post-meta"><span class="post-avatar mint">ი</span><div><strong>ინეს ბაღი</strong><small>გუშინ · 16:45</small></div><span class="visibility-chip butter">ჯგუფი: 3-4 წელი</span></div><h2>ახალი მასალები 3-4 წლის ჯგუფისთვის</h2><p>დღეიდან ვიწყებთ ახალ სენსორულ თამაშებს — ბავშვები აღფრთოვანებულები არიან 🎨</p><div class="post-actions"><span>❤️ 31</span><span>💬 12 კომენტარი</span></div></div></article>
                <article class="club-post"><div class="post-art lavender">გაკვეთილი</div><div class="post-body"><div class="post-meta"><span class="post-avatar mint">ი</span><div><strong>ინეს ბაღი</strong><small>2 დღის წინ</small></div><span class="visibility-chip leaf">მხოლოდ ბაღის მშობლებისთვის</span></div><h2>ღია გაკვეთილი მშობლებისთვის</h2><p>25 ივლისს, 11:00 საათზე, გელოდებით ღია გაკვეთილზე მშობლებისთვის. აქტიური მონაწილეობა მისასალმებელია!</p><div class="post-actions"><span>❤️ 18</span><span>💬 5 კომენტარი</span></div></div></article>
            </div>
            <aside class="club-sidebar"><article class="next-event"><strong>📌 შემდეგი ღონისძიება</strong><h3>საზაფხულო ზეიმი</h3><span>20 ივლისი · 17:00</span></article><article class="active-poll"><strong>აქტიური გამოკითხვა</strong><p>რომელი დროა უფრო მოსახერხებელი შემდეგი შეხვედრისთვის?</p><button type="button" data-club-tab-link="polls">ხმის მიცემა →</button></article></aside>
        </div>
    </section>

    <section class="club-panel" data-club-panel="events">
        <div class="event-list">
            <article class="event-card"><div class="event-art mint">ეზო</div><div><div class="event-meta"><span class="visibility-chip mint">კლუბის წევრები</span><span>📅 20 ივლისი, 17:00 · 📍 ბაღის ეზო</span></div><h2>საზაფხულო ზეიმი</h2><p>ცეკვა, თამაშები და ტკბილეული ყველა ჯგუფისთვის.</p><small>✓ 18 მოზრდილი · 22 ბავშვი დარეგისტრირდა</small></div><div class="event-actions"><button class="yes">✓ მოვალთ</button><button>ვერ მოვალთ</button></div></article>
            <article class="event-card"><div class="event-art lavender">ოთახი</div><div><div class="event-meta"><span class="visibility-chip leaf">მხოლოდ ბაღის მშობლებისთვის</span><span>📅 25 ივლისი, 11:00 · 📍 ჯგუფის ოთახი</span></div><h2>ღია გაკვეთილი</h2><p>აღმზრდელის დაკვირვება მშობლების თანდასწრებით.</p><small>✓ 9 მოზრდილი დარეგისტრირდა</small></div><div class="event-actions"><button class="yes">✓ მოვალთ</button><button>ვერ მოვალთ</button></div></article>
            <article class="event-card"><div class="event-art butter">საუზმე</div><div><div class="event-meta"><span class="visibility-chip butter">ჯგუფი: 3-4 წელი</span><span>📅 2 აგვისტო, 09:30 · 📍 ჯგუფის ოთახი</span></div><h2>3-4 ჯგუფის საუზმე მშობლებთან</h2><p>ერთობლივი საუზმე ბავშვებთან და აღმზრდელთან.</p><small>✓ 6 მოზრდილი · 11 ბავშვი დარეგისტრირდა</small></div><div class="event-actions"><button class="yes">✓ მოვალთ</button><button>ვერ მოვალთ</button></div></article>
        </div>
    </section>

    <section class="club-panel" data-club-panel="forum">
        <div class="forum-layout">
            <aside><strong>კატეგორიები</strong><button class="active">ზოგადი</button><button>კვება და ჯანმრთელობა</button><button>აღზრდა და განვითარება</button><button>კითხვები ბაღს <small>მშობლები</small></button></aside>
            <div class="forum-content"><div class="forum-head"><h2>ზოგადი</h2><button>+ ახალი თემა</button></div><div class="topic-list"><article><div><strong>📌 რა არის მშობელთა კლუბი?</strong><small>ნატო · 2 საათის წინ</small></div><span>💬 6</span></article><article><div><strong>შემდეგი შეხვედრის დრო</strong><small>დავითი · გუშინ</small></div><span>💬 12</span></article><article><div><strong>გავიცნოთ ერთმანეთი 👋</strong><small>თამარი · 2 დღის წინ</small></div><span>💬 24</span></article></div></div>
        </div>
    </section>

    <section class="club-panel" data-club-panel="polls">
        <div class="poll-list">
            <article class="poll-card"><div class="poll-meta"><span class="visibility-chip mint">კლუბის წევრები</span><span>⏰ 18 ივლისამდე</span></div><h2>რომელი დროა უფრო მოსახერხებელი შემდეგი შეხვედრისთვის?</h2><button><i style="width:68%"></i><span>17:00</span><strong>68%</strong></button><button><i style="width:22%"></i><span>18:00</span><strong>22%</strong></button><button><i style="width:10%"></i><span>შაბათი დილით</span><strong>10%</strong></button><small>31 ხმა · ანონიმური</small></article>
            <article class="poll-card"><div class="poll-meta"><span class="visibility-chip leaf">მხოლოდ ბაღის მშობლებისთვის</span><span>⏰ 25 ივლისამდე</span></div><h2>რომელი თემა გაინტერესებთ შემდეგ მშობელთა შეხვედრაზე?</h2><button><i style="width:45%"></i><span>ემოციური განვითარება</span><strong>45%</strong></button><button><i style="width:35%"></i><span>კვება</span><strong>35%</strong></button><button><i style="width:20%"></i><span>სკოლისთვის მზადება</span><strong>20%</strong></button><small>20 ხმა · ანონიმური</small></article>
        </div>
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
                    <div class="record-metrics"><div><span>ჯგუფი</span><strong>{{ $enrollment->group?->name ?? 'დასაზუსტებელია' }}</strong><small>{{ $enrollment->group?->academic_year }}</small></div><div><span>ჩარიცხვის სტატუსი</span><strong>{{ \App\Models\Enrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</strong><small>დაწყება: {{ $enrollment->starts_on->format('d.m.Y') }}</small></div><div><span>დღევანდელი დასწრება</span><strong>{{ $todayAttendance ? (\App\Models\AttendanceRecord::STATUSES[$todayAttendance->status] ?? $todayAttendance->status) : 'ჯერ არ დაფიქსირებულა' }}</strong><small>მოსვლა: {{ $todayAttendance?->checked_in_at?->format('H:i') ?? '—' }} · წასვლა: {{ $todayAttendance?->checked_out_at?->format('H:i') ?? '—' }}</small></div></div>
                    <div class="record-columns"><section><h3>ბოლო დასწრება</h3>@forelse($child->attendanceRecords->take(7) as $record)<div class="record-row"><div><strong>{{ $record->attendance_date->format('d.m.Y') }}</strong><small>{{ $record->group?->name }}</small></div><div><span>{{ \App\Models\AttendanceRecord::STATUSES[$record->status] ?? $record->status }}</span><small>{{ $record->checked_in_at?->format('H:i') ?? '—' }} — {{ $record->checked_out_at?->format('H:i') ?? '—' }}</small></div></div>@empty<p class="record-empty">დასწრების ჩანაწერი ჯერ არ არის.</p>@endforelse</section><section><h3>დარიცხვები</h3>@forelse($enrollment->payments as $payment)@php($effective=$payment->effectiveStatus())<div class="record-row"><div><strong>{{ $payment->period }}</strong><small>ვადა: {{ $payment->due_at?->format('d.m.Y') ?? '—' }}</small></div><div><span>{{ number_format($payment->outstandingAmount(),2) }} {{ $payment->currency }}</span><small>{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</small></div></div>@empty<p class="record-empty">დარიცხვა ჯერ არ არის შექმნილი.</p>@endforelse</section></div>
                @else
                    <p class="record-empty">ბავშვის პროფილი შექმნილია, თუმცა ჯგუფში ჩარიცხვა ჯერ არ არის დამატებული.</p>
                @endif
            </article>
        @empty
            <article class="empty-profile"><span>🌱</span><h2>ბავშვის პროფილი ჯერ არ არის დაკავშირებული</h2><p>ეს არის მშობლის დემო კაბინეტი. რეალური ჩარიცხვის დამტკიცების შემდეგ აქ გამოჩნდება ბავშვის ჯგუფი, დასწრება და ფინანსური ინფორმაცია.</p><a href="{{ route('home') }}#admission">ჩარიცხვის განაცხადი</a></article>
        @endforelse
    </section>
</main>
<script src="{{ asset('js/portal.js') }}"></script>
</body>
</html>
