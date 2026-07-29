<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>მშობლის კაბინეტი — ინეს ბაღი</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="portal-body">
<header class="portal-header">
    <a class="brand" href="{{ route('parent.dashboard') }}"><span class="logo">ი</span><span><strong>ინეს ბაღი</strong><small>მშობლის კაბინეტი</small></span></a>
    <div class="portal-user"><span>{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">გასვლა</button></form></div>
</header>

<main class="portal-main">
    <section class="portal-welcome">
        <div><span class="badge">დაცული სივრცე</span><h1>გამარჯობა, {{ auth()->user()->name }}</h1><p>აქ ჩანს მხოლოდ თქვენთან დაკავშირებული ბავშვების ჯგუფი, დასწრება და ფინანსური ინფორმაცია.</p></div>
        <a class="secondary" href="{{ route('home') }}">საჯარო საიტი</a>
    </section>

    @forelse($children as $child)
        @php($enrollment=$child->enrollments->first())
        @php($todayAttendance=$child->attendanceRecords->first(fn($record)=>$record->attendance_date->isToday()))
        <section class="child-portal-card">
            <div class="child-portal-head">
                <div class="child-avatar">{{ mb_substr($child->first_name,0,1) }}</div>
                <div><p class="eyebrow">ბავშვის პროფილი</p><h2>{{ $child->first_name }} {{ $child->last_name }}</h2><span>{{ $child->birth_date?->format('d.m.Y') ?? ($child->birth_year ? 'დაბადების წელი: '.$child->birth_year : 'დაბადების თარიღი დასაზუსტებელია') }}</span></div>
            </div>

            @if($enrollment)
                <div class="portal-grid">
                    <article><span>ჯგუფი</span><strong>{{ $enrollment->group?->name ?? 'დასაზუსტებელია' }}</strong><small>{{ $enrollment->group?->academic_year }}</small></article>
                    <article><span>ჩარიცხვის სტატუსი</span><strong>{{ \App\Models\Enrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</strong><small>დაწყება: {{ $enrollment->starts_on->format('d.m.Y') }}</small></article>
                    <article><span>დღევანდელი დასწრება</span><strong>{{ $todayAttendance ? (\App\Models\AttendanceRecord::STATUSES[$todayAttendance->status] ?? $todayAttendance->status) : 'ჯერ არ დაფიქსირებულა' }}</strong><small>მოსვლა: {{ $todayAttendance?->checked_in_at?->format('H:i') ?? '—' }} · წასვლა: {{ $todayAttendance?->checked_out_at?->format('H:i') ?? '—' }}</small></article>
                </div>

                <div class="portal-panel">
                    <div class="portal-panel-heading"><div><p class="eyebrow">დასწრება</p><h3>ბოლო ჩანაწერები</h3></div></div>
                    <div class="portal-attendance-list">
                        @forelse($child->attendanceRecords->take(7) as $record)
                            <article><div><strong>{{ $record->attendance_date->format('d.m.Y') }}</strong><small>{{ $record->group?->name }}</small></div><div class="attendance-result"><span class="status status-{{ $record->status==='present'?'approved':'new' }}">{{ \App\Models\AttendanceRecord::STATUSES[$record->status] ?? $record->status }}</span><small>{{ $record->checked_in_at?->format('H:i') ?? '—' }} — {{ $record->checked_out_at?->format('H:i') ?? '—' }}</small></div></article>
                        @empty
                            <p class="portal-empty">დასწრების ჩანაწერი ჯერ არ არის.</p>
                        @endforelse
                    </div>
                </div>

                <div class="portal-panel">
                    <div class="portal-panel-heading"><div><p class="eyebrow">ფინანსები</p><h3>დარიცხვები</h3></div></div>
                    <div class="portal-payment-list">
                        @forelse($enrollment->payments as $payment)
                            @php($effective=$payment->effectiveStatus())
                            <article>
                                <div><strong>{{ $payment->period }}</strong><small>გადახდის ვადა: {{ $payment->due_at?->format('d.m.Y') ?? '—' }}</small></div>
                                <div class="payment-amount"><div><strong>{{ number_format($payment->outstandingAmount(),2) }} {{ $payment->currency }}</strong><small>სრული: {{ number_format($payment->totalDue(),2) }} · გადახდილი: {{ number_format((float)$payment->paid_amount,2) }}</small></div><span class="status status-{{ in_array($effective,['paid','waived'],true)?'approved':($effective==='overdue'?'rejected':'new') }}">{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</span></div>
                            </article>
                        @empty
                            <p class="portal-empty">დარიცხვა ჯერ არ არის შექმნილი.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="portal-empty large">ბავშვის პროფილი შექმნილია, თუმცა ჯგუფში ჩარიცხვა ჯერ არ არის დამატებული.</div>
            @endif
        </section>
    @empty
        <section class="portal-empty-state"><h2>ბავშვის პროფილი ჯერ არ ჩანს</h2><p>თქვენი ანგარიში აქტიურია, თუმცა ბავშვის ჩანაწერი ჯერ არ არის დაკავშირებული. დაუკავშირდით ადმინისტრაციას.</p></section>
    @endforelse
</main>
</body>
</html>
