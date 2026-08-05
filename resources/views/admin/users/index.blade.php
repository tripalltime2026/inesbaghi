@extends('admin.layout')

@section('title', 'მომხმარებელთა ბაზა')
@section('heading', 'მომხმარებელთა ბაზა')

@section('content')
<section class="registry-hero">
    <div>
        <p class="eyebrow">მომხმარებლები და თანხები</p>
        <h2>რეგისტრაციები, დადასტურება და კლუბის წვდომა</h2>
        <p>თითოეული ანგარიში ავტომატურად ხვდება შესაბამის ბაზაში. ერთი შეხედვით ჩანს, ვის ელოდება დადასტურება, ვის აქვს კლუბი გახსნილი და ვის მონაცემებს სჭირდება მოწესრიგება.</p>
    </div>
    <div class="registry-total"><span>სულ ანგარიში</span><strong>{{ $counts['total'] }}</strong><small>{{ number_format($counts['outstanding'], 2) }} ₾ დარჩენილი თანხა</small></div>
</section>

<section class="registry-segment-grid" aria-label="მომხმარებელთა ბაზები">
    @foreach([
        'registered' => ['რეგისტრირებული', 'მოქმედი და განხილვაში მყოფი ანგარიშები', '◎'],
        'awaiting' => ['დადასტურებას ელოდება', 'კლუბზე ადმინის თანხმობა ჯერ არ აქვს', '◷'],
        'club_active' => ['კლუბის წევრები', 'ყველა პირობა შესრულებულია', '✓'],
        'approved_incomplete' => ['დამტკიცებული, მაგრამ არასრული', 'აკლია ბავშვი, ჯგუფი ან შესვლის მონაცემი', '!'],
        'suspended' => ['შეჩერებული', 'წვდომა დროებით დაბლოკილია', 'Ⅱ'],
        'cancelled' => ['გაუქმებული', 'დახურული ანგარიშები', '×'],
    ] as $key => [$label, $description, $icon])
        <a class="registry-segment-card segment-{{ $key }} {{ ($filters['segment'] ?? null) === $key ? 'active' : '' }}" href="{{ route('admin.users.index', ['segment' => $key]) }}">
            <span class="segment-icon">{{ $icon }}</span>
            <strong>{{ $counts[$key] }}</strong>
            <h3>{{ $label }}</h3>
            <p>{{ $description }}</p>
        </a>
    @endforeach
</section>

<section class="registry-health-row">
    <a href="{{ route('admin.users.index', ['segment' => 'no_access']) }}"><span>კლუბზე წვდომის გარეშე</span><strong>{{ $counts['no_access'] }}</strong></a>
    <a href="{{ route('admin.users.index', ['segment' => 'no_child']) }}"><span>ბავშვის გარეშე</span><strong>{{ $counts['no_child'] }}</strong></a>
    <a href="{{ route('admin.users.index', ['segment' => 'no_enrollment']) }}"><span>აქტიური ჯგუფის გარეშე</span><strong>{{ $counts['no_enrollment'] }}</strong></a>
    <a href="{{ route('admin.users.index', ['segment' => 'debt']) }}"><span>დავალიანების მქონე</span><strong>{{ $counts['debt'] }}</strong></a>
</section>

@if(session()->has('temporary_credentials'))
    <section class="registry-credentials" id="temporary-credentials">
        <div>
            <p class="eyebrow">ერთჯერადი ჩვენება</p>
            <h2>{{ session('temporary_credentials.name') }} — შესვლის მონაცემები</h2>
            <p>ეს პაროლი გვერდის დატოვების შემდეგ აღარ გამოჩნდება. გაუგზავნეთ მშობელს უსაფრთხო არხით.</p>
        </div>
        <div class="registry-credentials-grid">
            <label><span>ლოგინი</span><input readonly value="{{ session('temporary_credentials.username') }}" onclick="this.select()"></label>
            <label><span>დროებითი პაროლი</span><input readonly value="{{ session('temporary_credentials.password') }}" onclick="this.select()"></label>
            <label class="wide"><span>გასაგზავნი ტექსტი</span><textarea id="temporary-credentials-copy" readonly rows="4" onclick="this.select()">ინეს ბაღის ანგარიშზე შესვლის მონაცემები:
ლოგინი: {{ session('temporary_credentials.username') }}
დროებითი პაროლი: {{ session('temporary_credentials.password') }}
შესვლის შემდეგ შეცვალეთ პაროლი პროფილიდან.</textarea></label>
        </div>
        <button class="registry-primary" type="button" data-copy-temporary-credentials>ტექსტის კოპირება</button>
    </section>
@endif

<section class="registry-filter-panel">
    <form method="get" action="{{ route('admin.users.index') }}">
        <label class="registry-search"><span>ძიება</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="მშობელი, ბავშვი, ტელეფონი ან ელფოსტა"></label>
        <label><span>ბაზა</span><select name="segment"><option value="">ყველა ანგარიში</option>@foreach($segments as $key => $label)<option value="{{ $key }}" @selected(($filters['segment'] ?? null) === $key)>{{ $label }}</option>@endforeach</select></label>
        <label><span>ჯგუფი</span><select name="group_id"><option value="">ყველა ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string)($filters['group_id'] ?? '') === (string)$group->id)>{{ $group->name }}</option>@endforeach</select></label>
        <button class="registry-primary" type="submit">გაფილტვრა</button>
        <a class="registry-clear" href="{{ route('admin.users.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="registry-list-panel">
    <header class="registry-list-heading">
        <div><p class="eyebrow">{{ $users->total() }} შედეგი</p><h2>{{ isset($filters['segment']) ? ($segments[$filters['segment']] ?? 'მომხმარებლები') : 'ყველა მომხმარებელი' }}</h2></div>
        <p>ანგარიშის მდგომარეობა და კლუბის წვდომა ერთმანეთისგან დამოუკიდებლად იმართება.</p>
    </header>

    <div class="registry-user-list">
        @forelse($users as $registryUser)
            @php
                $enrollments = $registryUser->children->flatMap(fn($child) => $child->enrollments)->sortByDesc('created_at')->values();
                $latestEnrollment = $enrollments->first();
                $activeEnrollment = $enrollments->first(fn($enrollment) => $enrollment->status === 'active' && $enrollment->group?->is_active);
                $approved = $registryUser->isClubAccessApproved();
                $hasIdentity = $registryUser->hasVerifiedIdentity();
                $hasChild = $registryUser->children->isNotEmpty();
                $clubAccess = $registryUser->status === 'active' && $approved && $hasIdentity && $hasChild && $activeEnrollment;
                $outstanding = $registryUser->paymentOutstanding();
                $accountLabel = $accountStatuses[$registryUser->status] ?? $registryUser->status;

                if ($registryUser->status === 'cancelled') {
                    $accessReason = 'ანგარიში გაუქმებულია';
                } elseif ($registryUser->status === 'suspended') {
                    $accessReason = 'ანგარიში დროებით შეჩერებულია';
                } elseif ($registryUser->status === 'pending') {
                    $accessReason = 'ანგარიში განხილვის ეტაპზეა';
                } elseif (!$approved) {
                    $accessReason = 'საჭიროა ადმინისტრატორის დადასტურება';
                } elseif (!$hasIdentity) {
                    $accessReason = 'საჭიროა შესვლის მონაცემების დადასტურება';
                } elseif (!$hasChild) {
                    $accessReason = 'საჭიროა ბავშვის დაკავშირება';
                } elseif (!$activeEnrollment) {
                    $accessReason = 'საჭიროა აქტიურ ჯგუფში ჩარიცხვა';
                } else {
                    $accessReason = 'მშობელთა კლუბი გახსნილია';
                }
            @endphp

            <article class="registry-user-card account-{{ $registryUser->status }} {{ $clubAccess ? 'club-open' : 'club-closed' }}">
                <header class="registry-user-head">
                    <div class="registry-identity">
                        <i>{{ mb_substr($registryUser->name, 0, 1) }}</i>
                        <div><h3>{{ $registryUser->name }}</h3><p>{{ $registryUser->phone ?: 'ტელეფონი არ არის' }}@if($registryUser->email)<span>·</span>{{ $registryUser->email }}@endif</p><small>რეგისტრაცია: {{ $registryUser->created_at?->format('d.m.Y H:i') }}</small></div>
                    </div>
                    <div class="registry-status-stack">
                        <span class="account-chip account-chip-{{ $registryUser->status }}">{{ $accountLabel }}</span>
                        @if($approved)<span class="account-chip account-chip-active">ადმინის მიერ დადასტურებული</span>@endif
                        <span class="club-chip {{ $clubAccess ? 'open' : 'closed' }}">{{ $clubAccess ? 'კლუბი გახსნილია' : 'კლუბი დახურულია' }}</span>
                    </div>
                </header>

                <div class="registry-access-message {{ $clubAccess ? 'success' : 'warning' }}"><strong>{{ $accessReason }}</strong>@if(!$clubAccess)<span>ქვემოთ მოცემული ნაბიჯებიდან ჩანს, რა აკლია ანგარიშს.</span>@endif</div>

                <div class="registry-progress" aria-label="კლუბის წვდომის ეტაპები">
                    <div class="{{ $registryUser->status === 'active' ? 'done' : '' }}"><i>1</i><span>აქტიური ანგარიში</span></div>
                    <div class="{{ $approved ? 'done' : '' }}"><i>2</i><span>ადმინის დასტური</span></div>
                    <div class="{{ $hasIdentity ? 'done' : '' }}"><i>3</i><span>შესვლის მონაცემები</span></div>
                    <div class="{{ $hasChild ? 'done' : '' }}"><i>4</i><span>ბავშვი</span></div>
                    <div class="{{ $activeEnrollment ? 'done' : '' }}"><i>5</i><span>აქტიური ჯგუფი</span></div>
                </div>

                <div class="registry-info-grid">
                    <div><span>ბავშვი</span>@forelse($registryUser->children as $linkedChild)<a href="{{ route('admin.children.show', $linkedChild) }}">{{ $linkedChild->first_name }} {{ $linkedChild->last_name }}</a>@empty<strong>არ არის დაკავშირებული</strong>@endforelse</div>
                    <div><span>ჯგუფი</span><strong>{{ $activeEnrollment?->group?->name ?? $latestEnrollment?->group?->name ?? 'არ არის მინიჭებული' }}</strong><small>{{ $activeEnrollment ? 'აქტიური ჩარიცხვა' : ($latestEnrollment ? ($enrollmentStatuses[$latestEnrollment->status] ?? $latestEnrollment->status) : 'ჩარიცხვა არ არის') }}</small></div>
                    <div><span>განაცხადები</span><strong>{{ (int)$registryUser->application_count }}</strong></div>
                    <div class="{{ $outstanding > 0 ? 'money-due' : '' }}"><span>დარჩენილი თანხა</span><strong>{{ number_format($outstanding, 2) }} ₾</strong><small>{{ $registryUser->payment_due_at ? 'ვადა: '.$registryUser->payment_due_at->format('d.m.Y') : 'ვადა არ არის მითითებული' }}</small></div>
                </div>

                <div class="registry-card-actions">
                    <details class="registry-manage" {{ session('temporary_credentials.user_id') === $registryUser->id ? 'open' : '' }}>
                        <summary>ანგარიშის მართვა</summary>
                        <form method="post" action="{{ route('admin.users.access-payment.update', $registryUser) }}" class="registry-manage-form">
                            @csrf
                            @method('patch')
                            <label><span>ანგარიშის მდგომარეობა</span><select name="account_status" required>@foreach($accountStatuses as $key => $label)<option value="{{ $key }}" @selected($registryUser->status === $key)>{{ $label }}</option>@endforeach</select><small>შეჩერებულ და გაუქმებულ ანგარიშს სისტემაში შესვლა აღარ შეუძლია.</small></label>
                            <label class="registry-check wide"><input type="hidden" name="access_approved" value="0"><input type="checkbox" name="access_approved" value="1" @checked($approved)><span><strong>მშობლის კლუბზე წვდომის დამტკიცება</strong><small>კლუბი მხოლოდ აქტიური ანგარიშის, ბავშვისა და აქტიური ჯგუფის არსებობისას გაიხსნება.</small></span></label>
                            <label><span>სულ გადასახდელი ₾</span><input type="number" name="payment_due" value="{{ $registryUser->payment_due ?? 0 }}" min="0" step="0.01" required></label>
                            <label><span>გადახდილია ₾</span><input type="number" name="payment_paid" value="{{ $registryUser->payment_paid ?? 0 }}" min="0" step="0.01" required></label>
                            <label><span>გადახდის ვადა</span><input type="date" name="payment_due_at" value="{{ $registryUser->payment_due_at?->format('Y-m-d') }}"></label>
                            <label class="wide"><span>ადმინისტრაციული შენიშვნა</span><textarea name="payment_note" rows="3" maxlength="1500" placeholder="მაგალითად: აგვისტოს გადასახადი ან ანგარიშის სტატუსის მიზეზი">{{ $registryUser->payment_note }}</textarea></label>
                            <button class="registry-primary wide" type="submit">ცვლილებების შენახვა</button>
                        </form>
                    </details>

                    <details class="registry-manage">
                        <summary>ბავშვის შექმნა ან დაკავშირება</summary>
                        <form method="post" action="{{ route('admin.users.children.store', $registryUser) }}" class="registry-manage-form">
                            @csrf
                            <label class="wide"><span>არსებული ბავშვის არჩევა</span><select name="child_id"><option value="">ახალი ბავშვის შექმნა</option>@foreach($linkableChildren as $childOption)@unless($registryUser->children->contains('id', $childOption->id))<option value="{{ $childOption->id }}">{{ $childOption->first_name }} {{ $childOption->last_name }}@if($childOption->birth_date) · {{ $childOption->birth_date->format('d.m.Y') }}@elseif($childOption->birth_year) · {{ $childOption->birth_year }}@endif</option>@endunless @endforeach</select></label>
                            <label><span>ახალი ბავშვის სახელი</span><input name="first_name" placeholder="მაგ. ანა"></label>
                            <label><span>გვარი</span><input name="last_name" placeholder="გვარი"></label>
                            <label><span>დაბადების თარიღი</span><input type="date" name="birth_date"></label>
                            <label><span>ჯგუფი</span><select name="group_id"><option value="">ჯერ არ მივანიჭოთ</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
                            <label><span>ჩარიცხვის სტატუსი</span><select name="enrollment_status">@foreach($enrollmentStatuses as $key => $label)<option value="{{ $key }}" @selected($key === 'active')>{{ $label }}</option>@endforeach</select></label>
                            <label><span>დაწყების თარიღი</span><input type="date" name="starts_on" value="{{ now()->format('Y-m-d') }}"></label>
                            <button class="registry-primary wide" type="submit">ბავშვის დაკავშირება</button>
                        </form>
                    </details>

                    <form method="post" action="{{ route('admin.users.credentials.reset', $registryUser) }}" class="registry-credential-action" onsubmit="return confirm('ახალი დროებითი პაროლი შეცვლის მომხმარებლის მოქმედ პაროლს. გავაგრძელოთ?')">
                        @csrf @method('patch')
                        <div><strong>{{ $registryUser->username ?: 'ლოგინი ჯერ არ არის შექმნილი' }}</strong><small>{{ $registryUser->password ? 'დაცულია — არ ჩანს' : 'პაროლი ჯერ არ არის შექმნილი' }}</small></div>
                        <button type="submit">ახალი დროებითი პაროლი</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="registry-empty"><strong>მომხმარებელი ვერ მოიძებნა</strong><p>შეცვალეთ ბაზა, ჯგუფი ან საძიებო სიტყვა.</p></div>
        @endforelse
    </div>

    @if($users->hasPages())
        <nav class="registry-pagination">@if($users->onFirstPage())<span>← წინა</span>@else<a href="{{ $users->previousPageUrl() }}">← წინა</a>@endif<strong>{{ $users->currentPage() }} / {{ $users->lastPage() }}</strong>@if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">შემდეგი →</a>@else<span>შემდეგი →</span>@endif</nav>
    @endif
</section>

@if(session()->has('temporary_credentials'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.querySelector('[data-copy-temporary-credentials]');
    var field = document.getElementById('temporary-credentials-copy');
    if (!button || !field) return;
    button.addEventListener('click', function () {
        field.select();
        if (navigator.clipboard) navigator.clipboard.writeText(field.value);
        button.textContent = 'დაკოპირებულია';
    });
});
</script>
@endif
@endsection
