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

<section class="admin-section compact">
    <form class="filter-bar" method="get" action="{{ route('admin.users.index') }}">
        <label><span>ძიება</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="სახელი, ტელეფონი ან ელფოსტა"></label>
        <label><span>სტატუსი</span><select name="access"><option value="">ყველა მომხმარებელი</option>@foreach($accessFilters as $key=>$label)<option value="{{ $key }}" @selected(($filters['access'] ?? null)===$key)>{{ $label }}</option>@endforeach</select></label>
        <button class="primary" type="submit">მოძებნა</button>
        <a class="text-button" href="{{ route('admin.users.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="admin-section compact">
    <div class="panel-heading">
        <div><p class="eyebrow">{{ $users->total() }} ანგარიში</p><h2>დადასტურება და გადასახდელი ერთ ადგილას</h2><p>მონიშნეთ წვდომა, ჩაწერეთ თანხა და შეინახეთ. დაუდასტურებელი მომხმარებელი ჯგუფებსა და ფორუმს ვერ გახსნის.</p></div>
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
                    <div><span>ბავშვი</span><strong>{{ $registryUser->children->pluck('first_name')->join(', ') ?: 'არ არის დაკავშირებული' }}</strong></div>
                    <div><span>ჯგუფი</span><strong>{{ $latestEnrollment?->group?->name ?? 'არ არის მინიჭებული' }}</strong></div>
                    <div><span>განაცხადი</span><strong>{{ (int)$registryUser->application_count }}</strong></div>
                    <div><span>დარჩენილი</span><strong>{{ number_format($outstanding, 2) }} ₾</strong></div>
                </div>

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
@endsection
