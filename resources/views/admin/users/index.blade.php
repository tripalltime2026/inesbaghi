@extends('admin.layout')

@section('title', 'მომხმარებელთა ბაზა')
@section('heading', 'მომხმარებელთა ბაზა')

@section('content')
<section class="registry-hero">
    <div>
        <p class="eyebrow">მშობლები და ბავშვები</p>
        <h2>რეგისტრაცია → დადასტურება → ჯგუფი</h2>
        <p>მშობელი რეგისტრაციისას უკვე ქმნის და იბამს ბავშვს. თქვენ მხოლოდ ამოწმებთ კავშირს და ირჩევთ ბავშვის ჯგუფს — ცალკე ბავშვის მინიჭება აღარ არის საჭირო.</p>
    </div>
    <div class="registry-total"><span>სულ ანგარიში</span><strong>{{ $counts['total'] }}</strong><small>{{ number_format($counts['outstanding'], 2) }} ₾ დარჩენილი თანხა</small></div>
</section>

<section class="registry-segment-grid" aria-label="მომხმარებელთა ბაზები">
    @foreach([
        'registered' => ['რეგისტრირებული', 'მოქმედი და განხილვაში მყოფი ანგარიშები', '◎'],
        'awaiting' => ['დადასტურებას ელოდება', 'ბავშვი მიბმულია, ადმინის შემოწმება დარჩა', '◷'],
        'club_active' => ['კლუბის წევრები', 'დადასტურებული მშობელი და აქტიური ჯგუფი', '✓'],
        'approved_incomplete' => ['დამტკიცებული, მაგრამ არასრული', 'დადასტურებულია, თუმცა აქტიური ჯგუფი აკლია', '!'],
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
        <p>უსაფრთხოების მიზნით ადმინი მხოლოდ რეგისტრაციისას უკვე მიბმულ ბავშვს ხედავს. სხვა ბავშვის არჩევა ამ ეკრანიდან შეუძლებელია.</p>
    </header>

    <div class="registry-user-list">
        @forelse($users as $registryUser)
            @php
                $enrollments = $registryUser->children->flatMap(fn($child) => $child->enrollments)->sortByDesc('created_at')->values();
                $latestEnrollment = $enrollments->first();
                $activeEnrollment = $enrollments->first(fn($enrollment) => $enrollment->status === 'active' && $enrollment->group?->is_active);
                $approved = $registryUser->isClubAccessApproved();
                $hasChild = $registryUser->children->isNotEmpty();
                $clubAccess = $registryUser->canAccessParentClub();
                $outstanding = $registryUser->paymentOutstanding();
                $accountLabel = $accountStatuses[$registryUser->status] ?? $registryUser->status;

                if ($registryUser->status === 'cancelled') {
                    $accessReason = 'ანგარიში გაუქმებულია';
                } elseif ($registryUser->status === 'suspended') {
                    $accessReason = 'ანგარიში დროებით შეჩერებულია';
                } elseif ($registryUser->status === 'pending') {
                    $accessReason = 'ანგარიში განხილვის ეტაპზეა';
                } elseif (!$hasChild) {
                    $accessReason = 'რეგისტრაციაში ბავშვი არ არის მითითებული';
                } elseif (!$approved) {
                    $accessReason = 'ბავშვი უკვე მიბმულია — საჭიროა დადასტურება და ჯგუფის არჩევა';
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
                        @if($approved)<span class="account-chip account-chip-active">მშობელი დადასტურებულია</span>@endif
                        <span class="club-chip {{ $clubAccess ? 'open' : 'closed' }}">{{ $clubAccess ? 'კლუბი გახსნილია' : 'კლუბი დახურულია' }}</span>
                    </div>
                </header>

                <div class="registry-access-message {{ $clubAccess ? 'success' : 'warning' }}"><strong>{{ $accessReason }}</strong>@if(!$clubAccess)<span>ბავშვის ხელახლა მინიჭება საჭირო არ არის.</span>@endif</div>

                <div class="registry-progress" aria-label="კლუბის წვდომის ეტაპები">
                    <div class="{{ $registryUser->status === 'active' ? 'done' : '' }}"><i>1</i><span>ანგარიში</span></div>
                    <div class="{{ $hasChild ? 'done' : '' }}"><i>2</i><span>ბავშვი მიბმულია</span></div>
                    <div class="{{ $approved ? 'done' : '' }}"><i>3</i><span>ადმინის დასტური</span></div>
                    <div class="{{ $activeEnrollment ? 'done' : '' }}"><i>4</i><span>აქტიური ჯგუფი</span></div>
                </div>

                <div class="registry-info-grid">
                    <div><span>ბავშვი</span>@forelse($registryUser->children as $linkedChild)<a href="{{ route('admin.children.show', $linkedChild) }}">{{ $linkedChild->first_name }} {{ $linkedChild->last_name }}</a>@empty<strong>არ არის დაკავშირებული</strong>@endforelse</div>
                    <div><span>ჯგუფი</span><strong>{{ $activeEnrollment?->group?->name ?? $latestEnrollment?->group?->name ?? 'არ არის მინიჭებული' }}</strong><small>{{ $activeEnrollment ? 'აქტიური ჩარიცხვა' : ($latestEnrollment ? ($enrollmentStatuses[$latestEnrollment->status] ?? $latestEnrollment->status) : 'ადმინი ჯერ ჯგუფს არ არჩევს') }}</small></div>
                    <div><span>განაცხადები</span><strong>{{ (int)$registryUser->application_count }}</strong></div>
                    <div class="{{ $outstanding > 0 ? 'money-due' : '' }}"><span>დარჩენილი თანხა</span><strong>{{ number_format($outstanding, 2) }} ₾</strong><small>{{ $registryUser->payment_due_at ? 'ვადა: '.$registryUser->payment_due_at->format('d.m.Y') : 'ვადა არ არის მითითებული' }}</small></div>
                </div>

                <div class="registry-card-actions">
                    @if($hasChild)
                        <details class="registry-manage" @if(!$clubAccess) open @endif>
                            <summary>{{ $activeEnrollment ? 'ჯგუფის შეცვლა' : 'დადასტურება და ჯგუფში ჩარიცხვა' }}</summary>
                            <form method="post" action="{{ route('admin.users.children.store', $registryUser) }}" class="registry-manage-form">
                                @csrf
                                @if($registryUser->children->count() > 1)
                                    <label class="wide"><span>რეგისტრაციისას მიბმული ბავშვი</span><select name="child_id" required>@foreach($registryUser->children as $linkedChild)<option value="{{ $linkedChild->id }}">{{ $linkedChild->first_name }} {{ $linkedChild->last_name }}@if($linkedChild->birth_date) · {{ $linkedChild->birth_date->format('d.m.Y') }}@endif</option>@endforeach</select></label>
                                @else
                                    @php($linkedChild = $registryUser->children->first())
                                    <input type="hidden" name="child_id" value="{{ $linkedChild->id }}">
                                    <div class="wide"><span>რეგისტრაციისას მიბმული ბავშვი</span><strong>{{ $linkedChild->first_name }} {{ $linkedChild->last_name }}</strong>@if($linkedChild->birth_date)<small>{{ $linkedChild->birth_date->format('d.m.Y') }}</small>@endif</div>
                                @endif
                                <label><span>ჯგუფი</span><select name="group_id" required><option value="">აირჩიეთ ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((int)($activeEnrollment?->kindergarten_group_id ?? 0) === (int)$group->id)>{{ $group->name }}</option>@endforeach</select></label>
                                <label><span>დაწყების თარიღი</span><input type="date" name="starts_on" value="{{ $activeEnrollment?->starts_on?->format('Y-m-d') ?? now()->format('Y-m-d') }}" required></label>
                                <div class="empty-account wide">ამ მოქმედებით მშობლის კავშირი დადასტურდება, ბავშვი აქტიურ ჯგუფში ჩაირიცხება და Parent Club ავტომატურად გაიხსნება.</div>
                                <button class="registry-primary wide" type="submit">{{ $activeEnrollment ? 'ჯგუფის განახლება' : 'დადასტურება და ჩარიცხვა' }}</button>
                            </form>
                        </details>
                    @else
                        <div class="registry-access-message warning"><strong>ბავშვი არ არის მიბმული</strong><span>სხვა ბავშვს ამ ეკრანიდან ნუ მიაბამთ. გადაამოწმეთ რეგისტრაცია ან ჩარიცხვის განაცხადი.</span></div>
                    @endif

                    <details class="registry-manage" {{ session('temporary_credentials.user_id') === $registryUser->id ? 'open' : '' }}>
                        <summary>ანგარიშის და გადასახადის მართვა</summary>
                        <form method="post" action="{{ route('admin.users.access-payment.update', $registryUser) }}" class="registry-manage-form">
                            @csrf
                            @method('patch')
                            <label><span>ანგარიშის მდგომარეობა</span><select name="account_status" required>@foreach($accountStatuses as $key => $label)<option value="{{ $key }}" @selected($registryUser->status === $key)>{{ $label }}</option>@endforeach</select><small>შეჩერებულ და გაუქმებულ ანგარიშს სისტემაში შესვლა აღარ შეუძლია.</small></label>
                            <label class="registry-check wide"><input type="hidden" name="access_approved" value="0"><input type="checkbox" name="access_approved" value="1" @checked($approved)><span><strong>მშობლის კავშირის დადასტურება</strong><small>ჯგუფში ჩარიცხვისას ეს ავტომატურად ჩაირთვება. აქედან შეგიძლიათ ხელით გააუქმოთ.</small></span></label>
                            <label><span>სულ გადასახდელი ₾</span><input type="number" name="payment_due" value="{{ $registryUser->payment_due ?? 0 }}" min="0" step="0.01" required></label>
                            <label><span>გადახდილია ₾</span><input type="number" name="payment_paid" value="{{ $registryUser->payment_paid ?? 0 }}" min="0" step="0.01" required></label>
                            <label><span>გადახდის ვადა</span><input type="date" name="payment_due_at" value="{{ $registryUser->payment_due_at?->format('Y-m-d') }}"></label>
                            <label class="wide"><span>ადმინისტრაციული შენიშვნა</span><textarea name="payment_note" rows="3" maxlength="1500" placeholder="მაგალითად: აგვისტოს გადასახადი ან ანგარიშის სტატუსის მიზეზი">{{ $registryUser->payment_note }}</textarea></label>
                            <button class="registry-primary wide" type="submit">ცვლილებების შენახვა</button>
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
