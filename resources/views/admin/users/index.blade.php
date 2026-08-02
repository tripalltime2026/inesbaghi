@extends('admin.layout')

@section('title', 'მომხმარებლები და თანხები')
@section('heading', 'მომხმარებლები და თანხები')

@section('content')
<section class="stats-grid">
    <article class="stat-card"><span>სულ მომხმარებელი</span><strong>{{ $counts['total'] }}</strong><small>მშობლისა და წევრის ანგარიშები</small></article>
    <article class="stat-card"><span>დადასტურებას ელოდება</span><strong>{{ $counts['pending'] }}</strong><small>ჯგუფებსა და ფორუმზე წვდომა დახურულია</small></article>
    <article class="stat-card"><span>დადასტურებული</span><strong>{{ $counts['approved'] }}</strong><small>ადმინის თანხმობა მიღებულია</small></article>
    <article class="stat-card"><span>სულ დარჩენილი</span><strong>{{ number_format($counts['outstanding'], 2) }} ₾</strong><small>მომხმარებლებზე მითითებული დავალიანება</small></article>
</section>

@if(session()->has('temporary_credentials'))
    <section class="admin-section compact" id="temporary-credentials">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">ერთჯერადი ჩვენება</p>
                <h2>{{ session('temporary_credentials.name') }} — შესვლის მონაცემები</h2>
                <p>დროებითი პაროლი ამ გვერდის დატოვების შემდეგ აღარ გამოჩნდება. გაუგზავნეთ მომხმარებელს უსაფრთხო არხით.</p>
            </div>
        </div>
        <div class="cms-field-grid">
            <label><span>ლოგინი</span><input readonly value="{{ session('temporary_credentials.username') }}" onclick="this.select()"></label>
            <label><span>დროებითი პაროლი</span><input readonly value="{{ session('temporary_credentials.password') }}" onclick="this.select()"></label>
            <label class="wide">
                <span>გასაგზავნი ტექსტი</span>
                <textarea id="temporary-credentials-copy" readonly rows="4" onclick="this.select()">ინეს ბაღის ანგარიშზე შესვლის მონაცემები:
ლოგინი: {{ session('temporary_credentials.username') }}
დროებითი პაროლი: {{ session('temporary_credentials.password') }}
შესვლის შემდეგ შეცვალეთ პაროლი პროფილიდან.</textarea>
            </label>
        </div>
        <button class="primary" type="button" data-copy-temporary-credentials>ტექსტის კოპირება</button>
    </section>
@endif

<section class="admin-section compact">
    <form class="filter-bar" method="get" action="{{ route('admin.users.index') }}">
        <label><span>ძიება</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="სახელი, ლოგინი, ტელეფონი ან ელფოსტა"></label>
        <label><span>სტატუსი</span><select name="access"><option value="">ყველა მომხმარებელი</option>@foreach($accessFilters as $key=>$label)<option value="{{ $key }}" @selected(($filters['access'] ?? null)===$key)>{{ $label }}</option>@endforeach</select></label>
        <button class="primary" type="submit">მოძებნა</button>
        <a class="text-button" href="{{ route('admin.users.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="admin-section compact">
    <div class="panel-heading">
        <div><p class="eyebrow">{{ $users->total() }} ანგარიში</p><h2>მშობელი, ბავშვი, წვდომა და თანხა</h2><p>ბავშვი ცალკე რეგისტრაციას არ გადის. შექმენით ან აირჩიეთ ბავშვი, დაუკავშირეთ მშობელს და ის მშობლის პროფილში ავტომატურად გამოჩნდება.</p></div>
    </div>

    <div class="cms-item-list">
        @forelse($users as $registryUser)
            @php
                $enrollments = $registryUser->children->flatMap(fn($child) => $child->enrollments)->sortByDesc('created_at');
                $latestEnrollment = $enrollments->first();
                $approved = $registryUser->isClubAccessApproved();
                $clubAccess = $registryUser->canAccessParentClub();
                $outstanding = $registryUser->paymentOutstanding();
            @endphp
            <article class="cms-item-card">
                <div class="cms-card-top">
                    <div class="cms-card-identity">
                        <i style="background:#A9D3C9">{{ mb_substr($registryUser->name, 0, 1) }}</i>
                        <div>
                            <strong>{{ $registryUser->name }}</strong>
                            <small>{{ $registryUser->phone ?: 'ტელეფონი არ არის' }}@if($registryUser->email) · {{ $registryUser->email }}@endif</small>
                        </div>
                    </div>
                    <div>
                        <span class="status {{ $approved ? 'status-active' : 'status-pending' }}">{{ $approved ? 'ადმინის მიერ დადასტურებული' : 'დადასტურებას ელოდება' }}</span>
                        @if($approved && !$clubAccess)<small style="display:block;margin-top:6px">წვდომისთვის საჭიროა ბავშვთან კავშირი და აქტიური ჩარიცხვა.</small>@endif
                    </div>
                </div>

                <div class="account-meta" style="margin:14px 0">
                    <div>
                        <span>ბავშვი</span>
                        @forelse($registryUser->children as $linkedChild)
                            <a class="row-link" href="{{ route('admin.children.show', $linkedChild) }}"><strong>{{ $linkedChild->first_name }} {{ $linkedChild->last_name }}</strong></a>
                        @empty
                            <strong>არ არის დაკავშირებული</strong>
                        @endforelse
                    </div>
                    <div><span>ჯგუფი</span><strong>{{ $latestEnrollment?->group?->name ?? 'არ არის მინიჭებული' }}</strong></div>
                    <div><span>განაცხადი</span><strong>{{ (int)$registryUser->application_count }}</strong></div>
                    <div><span>დარჩენილი</span><strong>{{ number_format($outstanding, 2) }} ₾</strong></div>
                </div>

                <div class="account-meta" style="margin:14px 0">
                    <div><span>ლოგინი</span><strong>{{ $registryUser->username ?: 'ჯერ არ არის შექმნილი' }}</strong></div>
                    <div><span>პაროლი</span><strong>{{ $registryUser->password ? 'დაცულია — არ ჩანს' : 'ჯერ არ არის შექმნილი' }}</strong></div>
                </div>

                <form method="post" action="{{ route('admin.users.credentials.reset', $registryUser) }}" class="cms-item-form" onsubmit="return confirm('ახალი დროებითი პაროლი შეცვლის მომხმარებლის მოქმედ პაროლს. გავაგრძელოთ?')">
                    @csrf
                    @method('patch')
                    <div class="cms-form-actions">
                        <button class="primary" type="submit">დროებითი პაროლის შექმნა</button>
                        <small>მოქმედი პაროლის ნახვა შეუძლებელია. ახალი პაროლი მხოლოდ ერთხელ გამოჩნდება და ძველ პაროლს ჩაანაცვლებს.</small>
                    </div>
                </form>

                <details class="cms-create-box">
                    <summary>+ ბავშვის შექმნა ან დაკავშირება</summary>
                    <form method="post" action="{{ route('admin.users.children.store', $registryUser) }}" class="cms-item-form">
                        @csrf
                        <p style="margin:0 0 14px">თუ ბავშვი უკვე რეესტრშია, აირჩიეთ სიიდან. თუ ჯერ არ არსებობს, ჩაწერეთ მისი სახელი და მონაცემები.</p>
                        <div class="cms-field-grid">
                            <label class="wide">
                                <span>არსებული ბავშვის არჩევა</span>
                                <select name="child_id">
                                    <option value="">ახალი ბავშვის შექმნა</option>
                                    @foreach($linkableChildren as $childOption)
                                        @unless($registryUser->children->contains('id', $childOption->id))
                                            <option value="{{ $childOption->id }}">{{ $childOption->first_name }} {{ $childOption->last_name }}@if($childOption->birth_date) · {{ $childOption->birth_date->format('d.m.Y') }}@elseif($childOption->birth_year) · {{ $childOption->birth_year }}@endif</option>
                                        @endunless
                                    @endforeach
                                </select>
                            </label>
                            <label><span>ახალი ბავშვის სახელი</span><input name="first_name" placeholder="მაგ. ანა"></label>
                            <label><span>გვარი</span><input name="last_name" placeholder="გვარი"></label>
                            <label><span>დაბადების თარიღი</span><input type="date" name="birth_date"></label>
                            <label><span>დაბადების წელი</span><input type="number" name="birth_year" min="2018" max="{{ now()->year }}"></label>
                            <label><span>ჯგუფი</span><select name="group_id"><option value="">ჯერ არ მივანიჭოთ</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
                            <label><span>ჩარიცხვის სტატუსი</span><select name="enrollment_status">@foreach($enrollmentStatuses as $key=>$label)<option value="{{ $key }}" @selected($key === 'active')>{{ $label }}</option>@endforeach</select></label>
                            <label><span>დაწყების თარიღი</span><input type="date" name="starts_on" value="{{ now()->format('Y-m-d') }}"></label>
                        </div>
                        <button class="primary" type="submit">ბავშვის დაკავშირება</button>
                    </form>
                </details>

                <form method="post" action="{{ route('admin.users.access-payment.update', $registryUser) }}" class="cms-item-form">
                    @csrf
                    @method('patch')
                    <div class="cms-field-grid">
                        <label class="check-label wide">
                            <input type="hidden" name="access_approved" value="0">
                            <input type="checkbox" name="access_approved" value="1" {{ $approved ? 'checked' : '' }}>
                            <span><strong>დავადასტურო მშობლის წვდომა</strong><small>მონიშვნის გარეშე ჯგუფები და ფორუმი დახურულია.</small></span>
                        </label>
                        <label><span>სულ გადასახდელი ₾</span><input type="number" name="payment_due" value="{{ old('payment_due', $registryUser->payment_due ?? 0) }}" min="0" step="0.01" required></label>
                        <label><span>გადახდილია ₾</span><input type="number" name="payment_paid" value="{{ old('payment_paid', $registryUser->payment_paid ?? 0) }}" min="0" step="0.01" required></label>
                        <label><span>გადახდის ვადა</span><input type="date" name="payment_due_at" value="{{ old('payment_due_at', $registryUser->payment_due_at?->format('Y-m-d')) }}"></label>
                        <label class="wide"><span>შენიშვნა</span><textarea name="payment_note" rows="3" placeholder="მაგალითად: აგვისტოს გადასახადი">{{ old('payment_note', $registryUser->payment_note) }}</textarea></label>
                    </div>
                    <div class="cms-form-actions">
                        <button class="primary" type="submit">შენახვა</button>
                        @if($clubAccess)<span class="status status-active">კლუბი გახსნილია</span>@else<span class="status status-cancelled">კლუბი დახურულია</span>@endif
                    </div>
                </form>
            </article>
        @empty
            <div class="empty-state">მომხმარებელი ვერ მოიძებნა.</div>
        @endforelse
    </div>

    @if($users->hasPages())
        <nav class="pagination">@if($users->onFirstPage())<span>← წინა</span>@else<a href="{{ $users->previousPageUrl() }}">← წინა</a>@endif<strong>{{ $users->currentPage() }} / {{ $users->lastPage() }}</strong>@if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">შემდეგი →</a>@else<span>შემდეგი →</span>@endif</nav>
    @endif
</section>

@if(session()->has('temporary_credentials'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.querySelector('[data-copy-temporary-credentials]');
    var field = document.getElementById('temporary-credentials-copy');

    if (!button || !field) {
        return;
    }

    button.addEventListener('click', function () {
        field.select();

        if (navigator.clipboard) {
            navigator.clipboard.writeText(field.value);
        }
    });
});
</script>
@endif
@endsection
