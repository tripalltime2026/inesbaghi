<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>მშობლის პირადი კაბინეტი — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/club-groups.css') }}?v=20260805">
    <link rel="stylesheet" href="{{ asset('css/parent-forum.css') }}">
    <link rel="stylesheet" href="{{ asset('css/smart-parent-club.css') }}?v=20260805">
</head>
<body class="club-body smart-club-body">
<header class="club-header smart-club-header">
    <a class="club-brand" href="{{ route('home') }}"><span class="club-arch"><i></i></span><span><strong>ინეს ბაღი</strong><small>მშობლის პირადი კაბინეტი</small></span></a>
    <nav><a href="{{ route('parent.dashboard') }}#feed">ჩემი სივრცე</a><a href="{{ route('parent.dashboard') }}#events">ღონისძიებები</a><a href="{{ route('parent.dashboard') }}#forum">მშობელთა კლუბი</a><a href="{{ route('parent.dashboard') }}#children">ბავშვი</a></nav>
    <div class="club-head-actions"><span class="head-pill navy">{{ $user->membershipLabel() }}</span><span class="club-user"><i>{{ mb_substr($user->name,0,1) }}</i>{{ $user->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-button">გასვლა</button></form></div>
</header>

<main class="club-main smart-club-main">
    @if(session('success'))<div class="smart-flash success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="smart-flash error">@foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach</div>@endif

    <section class="smart-welcome">
        <div><span class="club-eyebrow">მშობლის პირადი კაბინეტი</span><h1>გამარჯობა, {{ $user->name }}</h1><p>აქ ხედავთ ბავშვის ყოველდღიურ ინფორმაციას, ღონისძიებებს, ადმინისტრაციის პასუხებს და თქვენი ჯგუფის მშობლებთან დახურულ საუბარს.</p></div>
        <div class="smart-welcome-actions"><button type="button" data-club-tab-link="forum">კითხვის დასმა</button><button type="button" data-club-tab-link="events">ღონისძიებების ნახვა</button></div>
    </section>

    <section class="smart-summary" aria-label="პირადი კაბინეტის მოკლე მიმოხილვა">
        <article><span>შეტყობინებები</span><strong>{{ $summary['unread_notifications'] }}</strong><small>წაუკითხავი ინფორმაცია</small></article>
        <article><span>ღონისძიებები</span><strong>{{ $summary['upcoming_events'] }}</strong><small>მომავალი შეხვედრა</small></article>
        <article><span>ჩემი კითხვები</span><strong>{{ $summary['open_questions'] }}</strong><small>პასუხის მოლოდინში</small></article>
        <article class="{{ $summary['outstanding_payment'] > 0 ? 'attention' : '' }}"><span>გადასახდელი</span><strong>{{ number_format($summary['outstanding_payment'], 2) }} ₾</strong><small>{{ $summary['outstanding_payment'] > 0 ? 'დარჩენილი თანხა' : 'დავალიანება არ არის' }}</small></article>
    </section>

    <section class="club-group-switcher" aria-label="ბავშვის ჯგუფის არჩევა">
        <div><small>დახურული კლუბი</small><strong>აირჩიეთ ბავშვის ჯგუფი</strong></div>
        <div class="club-group-buttons">
            @forelse($clubGroups as $group)
                <button type="button" data-club-group="{{ $group->id }}" class="{{ $loop->first ? 'active' : '' }}"><span>{{ $group->name }}</span><small>{{ $group->academic_year }}</small></button>
            @empty
                <span class="club-no-group">აქტიური ჯგუფი ვერ მოიძებნა.</span>
            @endforelse
        </div>
    </section>

    <nav class="club-tabs smart-tabs" aria-label="პირადი კაბინეტის სექციები">
        <button class="active" type="button" data-club-tab="feed">ჩემი სივრცე</button>
        <button type="button" data-club-tab="events">ღონისძიებები</button>
        <button type="button" data-club-tab="forum">მშობელთა კლუბი</button>
        <button type="button" data-club-tab="children">ბავშვი და ფინანსები</button>
        <button type="button" data-club-tab="profile">პროფილი</button>
    </nav>

    <section class="club-panel active" data-club-panel="feed">
        <div class="smart-dashboard-grid">
            <div>
                <div class="smart-section-head"><div><small>თქვენი ჯგუფი</small><h2>სიახლეები და მნიშვნელოვანი ინფორმაცია</h2></div><button type="button" data-club-tab-link="forum">+ კითხვა</button></div>
                <div class="feed-list"><div class="club-loading">ჯგუფის სიახლეები იტვირთება…</div></div>

                <section class="smart-my-questions">
                    <div class="smart-section-head"><div><small>პასუხების კონტროლი</small><h2>ჩემი ბოლო კითხვები</h2></div><button type="button" data-club-tab-link="forum">ყველა საუბარი</button></div>
                    @forelse($myTopics as $topic)
                        @php($officialAnswer = $topic->comments->first())
                        <article id="my-topic-{{ $topic->id }}" class="smart-question {{ $topic->status }}">
                            <div><span>{{ \App\Models\ForumTopic::STATUSES[$topic->status] ?? $topic->status }}</span><strong>{{ $topic->title }}</strong><small>{{ $topic->group?->name }} · {{ $topic->created_at?->format('d.m.Y H:i') }} · {{ $topic->comments_count }} პასუხი</small></div>
                            @if($officialAnswer)<p><b>ადმინისტრაციის პასუხი:</b> {{ $officialAnswer->body }}</p>@else<p>პასუხის მიღებისთანავე შეტყობინებას აქვე ნახავთ.</p>@endif
                        </article>
                    @empty
                        <div class="smart-empty"><strong>კითხვა ჯერ არ დაგისვამთ</strong><p>მშობელთა კლუბში შეგიძლიათ ჰკითხოთ ადმინისტრაციას ან თქვენი ჯგუფის მშობლებს.</p></div>
                    @endforelse
                </section>
            </div>

            <aside class="smart-notifications">
                <div class="smart-section-head"><div><small>პირადი ცენტრი</small><h2>შეტყობინებები</h2></div>@if($summary['unread_notifications'])<form method="post" action="{{ route('parent.notifications.read-all') }}">@csrf @method('PATCH')<button type="submit">ყველას წაკითხვა</button></form>@endif</div>
                <div class="smart-notification-list">
                    @forelse($notifications as $notification)
                        <form method="post" action="{{ route('parent.notifications.read', $notification) }}" class="smart-notification {{ $notification->read_at ? 'read' : 'unread' }}">
                            @csrf @method('PATCH')
                            <button type="submit"><span>{{ $notification->read_at ? '✓' : '●' }}</span><div><strong>{{ $notification->title }}</strong>@if($notification->body)<p>{{ $notification->body }}</p>@endif<small>{{ $notification->created_at?->diffForHumans() }}</small></div></button>
                        </form>
                    @empty
                        <div class="smart-empty compact"><strong>ახალი შეტყობინება არ არის</strong><p>ღონისძიებები და პასუხები აქ გამოჩნდება.</p></div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>

    <section class="club-panel" data-club-panel="events" id="events">
        <div class="smart-section-head"><div><small>კალენდარი და მონაწილეობა</small><h2>მომავალი ღონისძიებები</h2><p>დაადასტურეთ მონაწილეობა, რათა ადმინისტრაციამ მონაწილეთა რაოდენობა წინასწარ იცოდეს.</p></div></div>
        <div class="smart-event-list">
            @forelse($events as $event)
                @php($myResponse = $event->responses->first())
                <article class="smart-event {{ $event->is_featured ? 'featured' : '' }}">
                    <div class="smart-event-date"><strong>{{ $event->starts_at->format('d') }}</strong><span>{{ $event->starts_at->translatedFormat('M') }}</span><small>{{ $event->starts_at->format('H:i') }}</small></div>
                    <div class="smart-event-content"><div class="smart-event-meta"><span>{{ $event->audienceLabel() }}</span>@if($event->is_featured)<b>მნიშვნელოვანი</b>@endif</div><h3>{{ $event->title }}</h3><p>{{ $event->description }}</p><small>{{ $event->location ? '📍 '.$event->location.' · ' : '' }}{{ $event->going_count }} ოჯახი მოდის @if($event->capacity) · ადგილი {{ $event->capacity }} ოჯახისთვის @endif</small></div>
                    <form method="post" action="{{ route('parent.events.response', $event) }}" class="smart-rsvp">
                        @csrf
                        <span>თქვენი პასუხი</span>
                        <div>
                            @foreach(\App\Models\ClubEvent::RESPONSE_STATUSES as $value => $label)
                                <button type="submit" name="status" value="{{ $value }}" class="{{ $myResponse?->status === $value ? 'active' : '' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </form>
                </article>
            @empty
                <div class="smart-empty"><strong>ღონისძიება ჯერ არ არის დაგეგმილი</strong><p>ახალი ღონისძიების გამოქვეყნებისთანავე შეტყობინებას მიიღებთ.</p></div>
            @endforelse
        </div>
    </section>

    <section class="club-panel" data-club-panel="forum" id="forum">
        <div class="smart-forum-intro"><div><small>დახურული და უსაფრთხო სივრცე</small><h2>მშობელთა კლუბი</h2><p>დასვით კითხვა, გაუზიარეთ გამოცდილება სხვა მშობლებს და მიიღეთ ადმინისტრაციის ოფიციალური პასუხი. პირადი საკონტაქტო მონაცემები სხვა წევრებს არ უჩანს.</p></div></div>
        <div class="forum-layout"><aside class="forum-sidebar"><div class="club-loading">ჯგუფის წევრები იტვირთება…</div></aside><div class="forum-content"><div class="club-loading">ჯგუფის საუბრები იტვირთება…</div></div></div>
    </section>

    <section class="club-panel" data-club-panel="children" id="children">
        <div class="smart-section-head"><div><small>ყოველდღიური კონტროლი</small><h2>ბავშვი, დასწრება და ფინანსები</h2></div></div>
        @forelse($children as $child)
            @php($enrollment = $child->enrollments->first())
            @php($todayAttendance = $child->attendanceRecords->first(fn($record) => $record->attendance_date->isToday()))
            <article class="smart-child-card">
                <header><span>{{ mb_substr($child->first_name,0,1) }}</span><div><small>ბავშვის პროფილი</small><h2>{{ $child->first_name }} {{ $child->last_name }}</h2><p>{{ $child->birth_date?->format('d.m.Y') ?? 'დაბადების თარიღი დასაზუსტებელია' }}</p></div></header>
                @if($enrollment)
                    <div class="smart-child-metrics"><div><span>ჯგუფი</span><strong>{{ $enrollment->group?->name ?? 'დასაზუსტებელია' }}</strong><small>{{ $enrollment->group?->academic_year }}</small></div><div><span>დღევანდელი დასწრება</span><strong>{{ $todayAttendance ? (\App\Models\AttendanceRecord::STATUSES[$todayAttendance->status] ?? $todayAttendance->status) : 'ჯერ არ დაფიქსირებულა' }}</strong><small>{{ $todayAttendance?->checked_in_at?->format('H:i') ?? '—' }} — {{ $todayAttendance?->checked_out_at?->format('H:i') ?? '—' }}</small></div><div><span>ჩარიცხვა</span><strong>{{ \App\Models\Enrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</strong><small>დაწყება: {{ $enrollment->starts_on?->format('d.m.Y') ?? '—' }}</small></div></div>
                    <div class="smart-child-columns"><section><h3>ბოლო დასწრება</h3>@forelse($child->attendanceRecords->take(7) as $record)<div class="smart-record"><div><strong>{{ $record->attendance_date->format('d.m.Y') }}</strong><small>{{ $record->group?->name }}</small></div><div><span>{{ \App\Models\AttendanceRecord::STATUSES[$record->status] ?? $record->status }}</span><small>{{ $record->checked_in_at?->format('H:i') ?? '—' }} — {{ $record->checked_out_at?->format('H:i') ?? '—' }}</small></div></div>@empty<p>დასწრების ჩანაწერი ჯერ არ არის.</p>@endforelse</section><section><h3>დარიცხვები</h3>@forelse($enrollment->payments as $payment)@php($effective = $payment->effectiveStatus())<div class="smart-record"><div><strong>{{ $payment->period }}</strong><small>ვადა: {{ $payment->due_at?->format('d.m.Y') ?? '—' }}</small></div><div><span>{{ number_format($payment->outstandingAmount(),2) }} {{ $payment->currency }}</span><small>{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</small></div></div>@empty<p>დარიცხვა ჯერ არ არის შექმნილი.</p>@endforelse</section></div>
                @else
                    <div class="smart-empty compact"><strong>ჯგუფი ჯერ არ არის მინიჭებული</strong><p>ადმინისტრატორი ამუშავებს ბავშვის ჩარიცხვის მონაცემებს.</p></div>
                @endif
            </article>
        @empty
            <div class="smart-empty"><strong>ბავშვის პროფილი არ არის დაკავშირებული</strong><p>დაუკავშირდით ადმინისტრაციას ან შეავსეთ ჩარიცხვის განაცხადი.</p></div>
        @endforelse
    </section>

    <section class="club-panel" data-club-panel="profile" id="profile">
        <div class="smart-profile-grid">
            <article class="smart-profile-card"><small>ანგარიშის მონაცემები</small><h2>{{ $user->name }}</h2><dl><div><dt>ელფოსტა</dt><dd>{{ $user->email ?: 'არ არის მითითებული' }}</dd></div><div><dt>ტელეფონი</dt><dd>{{ $user->phone ?: 'არ არის მითითებული' }}</dd></div><div><dt>სტატუსი</dt><dd>{{ $user->membershipLabel() }}</dd></div></dl><a href="{{ route('account.profile') }}">პროფილის რედაქტირება</a></article>
            <article class="smart-preferences"><small>ჭკვიანი შეტყობინებები</small><h2>რას მიიღებთ პირად კაბინეტში</h2><form method="post" action="{{ route('parent.preferences.update') }}">@csrf @method('PATCH')
                @foreach(['event_updates' => 'ახალი ღონისძიებები და ცვლილებები','forum_replies' => 'პასუხები ჩემს კითხვებსა და საუბარზე','payment_reminders' => 'გადახდის ვადის შეხსენება','weekly_digest' => 'კვირის მოკლე შეჯამება'] as $field => $label)
                    <input type="hidden" name="{{ $field }}" value="0"><label><span>{{ $label }}</span><input type="checkbox" name="{{ $field }}" value="1" @checked($preferences->{$field})></label>
                @endforeach
                <button type="submit">პარამეტრების შენახვა</button>
            </form></article>
        </div>
    </section>
</main>
<script src="{{ asset('js/portal.js') }}"></script>
<script src="{{ asset('js/cms-portal.js') }}?v=20260805"></script>
</body>
</html>
