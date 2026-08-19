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
        $activeEnrollments = $children
            ->flatMap(fn($child) => $child->enrollments)
            ->filter(fn($enrollment) => $enrollment->status === 'active' && $enrollment->group?->is_active)
            ->values();
        $activeEnrollment = $activeEnrollments->first();
        $activeGroupNames = $activeEnrollments->pluck('group.name')->filter()->unique()->implode(', ');
    @endphp

    <section class="account-hero">
        <p class="account-kicker">{{ $user->membershipLabel() }}</p>
        <h1>გამარჯობა, {{ $user->name }}</h1>
        @if($hasChild)
            <p>თქვენს ანგარიშზე დაკავშირებულია {{ $children->count() }} ბავშვი. თითო ბავშვს აქვს თავისი ჯგუფი და თავისი ფინანსური ისტორია; ქვემოთ მხოლოდ თქვენი ოჯახის მონაცემებია.</p>
        @else
            <p><strong>ბავშვის მიბმა აუცილებელია.</strong> ბავშვის მონაცემი ანგარიშთან ვერ მოიძებნა. Parent Club-ზე წვდომა და ჯგუფში ჩარიცხვა ვერ გააქტიურდება, სანამ ბავშვის მონაცემებს პროფილში არ დაამატებთ.</p>
            <a class="account-cta" href="{{ route('account.profile') }}" style="display:inline-flex;margin-top:14px">ბავშვის მიბმა →</a>
        @endif
    </section>

    <section class="status-step-grid" aria-label="ანგარიშის სტატუსის ეტაპები">
        <article class="status-step done"><span>1</span><h2>ანგარიში</h2><p>რეგისტრაცია დასრულებულია.</p></article>
        <article class="status-step {{ $hasChild ? 'done' : 'waiting' }}"><span>2</span><h2>ბავშვები</h2><p>{{ $hasChild ? $children->count().' ბავშვი დაკავშირებულია.' : 'სავალდებულოა — დაამატეთ პროფილიდან.' }}</p></article>
        <article class="status-step {{ ($approved && $activeEnrollment) ? 'done' : 'waiting' }}"><span>3</span><h2>ჯგუფები</h2><p>{{ ($approved && $activeEnrollment) ? 'აქტიური ჯგუფი მინიჭებულია.' : ($hasChild ? 'ადმინისტრატორის ჩარიცხვას ელოდება.' : 'ბავშვის მიბმის შემდეგ გახდება ხელმისაწვდომი.') }}</p></article>
        <article class="status-step {{ $clubAccess ? 'done' : 'waiting' }}"><span>4</span><h2>Parent Club</h2><p>{{ $clubAccess ? 'წვდომა გახსნილია.' : 'პირობების შესრულების შემდეგ ავტომატურად გაიხსნება.' }}</p></article>
    </section>

    <section class="account-grid">
        <div class="account-panel">
            <h2>ბავშვები და ჯგუფები</h2>
            @forelse($children as $child)
                @php($enrollment = $child->enrollments->first(fn($item) => $item->status === 'active' && $item->group?->is_active) ?? $child->enrollments->sortByDesc('created_at')->first())
                <article class="account-record">
                    <strong>{{ $child->first_name }} {{ $child->last_name }}</strong>
                    <small>{{ $enrollment?->group?->name ?? 'ადმინისტრატორი ჯერ ჯგუფს არჩევს' }}</small>
                    @if($enrollment)
                        <span class="account-badge {{ $enrollment->status === 'active' ? 'active' : '' }}">{{ \App\Models\Enrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</span>
                    @else
                        <span class="account-badge blocked">ჩარიცხვის მოლოდინში</span>
                    @endif
                </article>
            @empty
                <div class="empty-account"><strong>ბავშვის მიბმა სავალდებულოა.</strong><br>ბავშვის მონაცემი ანგარიშთან ვერ მოიძებნა. გადადით პროფილში და შეავსეთ ბავშვის სახელი, გვარი და დაბადების თარიღი.</div>
            @endforelse
            @if($hasChild)<a class="account-cta" href="{{ route('account.profile') }}" style="margin-top:14px">+ ბავშვის დამატება</a>@endif
        </div>

        <aside class="account-panel">
            <h2>წვდომის მდგომარეობა</h2>
            <div class="account-meta">
                <div><span>ბავშვები</span><strong>{{ $hasChild ? $children->count() : 'სავალდებულოა' }}</strong></div>
                <div><span>ადმინის დასტური</span><strong>{{ $approved ? 'დადასტურებულია' : 'მოლოდინშია' }}</strong></div>
                <div><span>აქტიური ჯგუფები</span><strong>{{ $activeGroupNames ?: 'ჯერ არ არის' }}</strong></div>
                <div><span>Parent Club</span><strong>{{ $clubAccess ? 'გახსნილია' : 'დახურულია' }}</strong></div>
            </div>
            @if($clubAccess)
                <a class="account-cta" href="{{ route('parent.dashboard') }}">Parent Club-ში გადასვლა →</a>
            @elseif($hasChild)
                <div class="empty-account" style="margin-top:16px">ადმინისტრატორის მიერ მინიმუმ ერთი ბავშვის აქტიურ ჯგუფში ჩარიცხვის შემდეგ Parent Club გაიხსნება.</div>
            @else
                <div class="empty-account" style="margin-top:16px"><strong>დასასრულებელია:</strong> ბავშვის მონაცემი ანგარიშთან ვერ მოიძებნა — ჯერ მიაბით ბავშვი პროფილიდან.</div>
                <a class="account-cta" href="{{ route('account.profile') }}" style="margin-top:14px">პროფილში გადასვლა →</a>
            @endif
        </aside>
    </section>

    <section class="account-grid">
        <div class="account-panel">
            <h2>ჩემი ოჯახის გადასახდელი</h2>
            <div class="account-meta">
                <div><span>დადასტურებული დარიცხვები</span><strong>{{ $familyPayments->count() }}</strong></div>
                <div><span>გადახდილია</span><strong>{{ number_format($familyPaid, 2) }} ₾</strong></div>
                <div><span>დარჩენილი</span><strong>{{ number_format($familyOutstanding, 2) }} ₾</strong></div>
                <div><span>კონფიდენციალურობა</span><strong>მხოლოდ თქვენი ოჯახი</strong></div>
            </div>

            @forelse($children as $child)
                @php($childPayments = $child->enrollments->flatMap(fn($enrollment) => $enrollment->payments)->reject(fn($payment) => in_array($payment->status, ['cancelled','waived'], true))->sortByDesc('period')->values())
                <article class="account-record" style="margin-top:12px">
                    <strong>{{ $child->first_name }} {{ $child->last_name }} — {{ number_format($childPayments->sum(fn($payment) => $payment->outstandingAmount()), 2) }} ₾ დარჩენილი</strong>
                    @if($childPayments->isNotEmpty())
                        @foreach($childPayments->take(3) as $payment)
                            <small style="display:block;margin-top:5px">{{ $payment->period }} · {{ number_format($payment->totalDue(),2) }} ₾ · გადახდილი {{ number_format((float)$payment->paid_amount,2) }} ₾ · ვადა {{ $payment->due_at?->format('d.m.Y') ?? '—' }}</small>
                        @endforeach
                    @else
                        <small>დადასტურებული დარიცხვა ჯერ არ არის.</small>
                    @endif
                </article>
            @empty
                <div class="empty-account">ფინანსური ინფორმაცია ბავშვის მიბმის შემდეგ გამოჩნდება.</div>
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
                <div class="empty-account">{{ $hasChild ? 'ცალკე ჩარიცხვის განაცხადი არ არის. ბავშვები თქვენს ანგარიშთან უკვე დაკავშირებულია.' : 'ჯერ მიაბით ბავშვი პროფილიდან; შემდეგ ადმინისტრატორი შეძლებს ჯგუფში ჩარიცხვას.' }}</div>
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
