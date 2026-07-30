@extends('admin.layout')

@section('title', 'მომხმარებელთა რეესტრი')
@section('heading', 'მომხმარებელთა რეესტრი')

@section('content')
<section class="stats-grid">
    <article class="stat-card"><span>სულ ანგარიში</span><strong>{{ $counts['total'] }}</strong><small>ყველა რეგისტრირებული მომხმარებელი</small></article>
    <article class="stat-card"><span>ბავშვის გარეშე</span><strong>{{ $counts['registered'] }}</strong><small>რეგისტრაცია გავლილია, მაგრამ მშობელი არ არის დადასტურებული</small></article>
    <article class="stat-card"><span>ბავშვთან დაკავშირებული</span><strong>{{ $counts['linked'] }}</strong><small>მეურვის კავშირი შექმნილია</small></article>
    <article class="stat-card"><span>კლუბზე დაშვებული</span><strong>{{ $counts['club'] }}</strong><small>აქტიური ჩარიცხვა და დადასტურებული ნომერი</small></article>
</section>

<section class="admin-section compact">
    <form class="filter-bar" method="get" action="{{ route('admin.users.index') }}">
        <label><span>ძიება</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="სახელი, ტელეფონი ან ელფოსტა"></label>
        <label><span>მომხმარებლის მდგომარეობა</span><select name="membership"><option value="">ყველა მომხმარებელი</option>@foreach($membershipFilters as $key=>$label)<option value="{{ $key }}" @selected(($filters['membership'] ?? null)===$key)>{{ $label }}</option>@endforeach</select></label>
        <button class="primary" type="submit">გაფილტვრა</button>
        <a class="text-button" href="{{ route('admin.users.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">{{ $users->total() }} ჩანაწერი</p><h2>ანგარიშები და მშობლის სტატუსი</h2></div></div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>მომხმარებელი</th><th>ანგარიში</th><th>განაცხადი</th><th>ბავშვი</th><th>ჩარიცხვა</th><th>კლუბი</th></tr></thead>
            <tbody>
            @forelse($users as $registryUser)
                @php
                    $enrollments = $registryUser->children->flatMap(fn($child) => $child->enrollments)->sortByDesc('created_at');
                    $latestEnrollment = $enrollments->first();
                    $clubAccess = $registryUser->canAccessParentClub();
                @endphp
                <tr>
                    <td><strong>{{ $registryUser->name }}</strong><small>{{ $registryUser->phone }}@if($registryUser->email) · {{ $registryUser->email }}@endif</small></td>
                    <td><span class="status {{ $registryUser->phone_verified_at ? 'status-active' : 'status-pending' }}">{{ $registryUser->phone_verified_at ? 'დადასტურებული' : 'დაუდასტურებელი' }}</span><small>{{ $registryUser->role }}</small></td>
                    <td>@if((int)$registryUser->application_count > 0)<strong>{{ $registryUser->application_count }}</strong><small>განაცხადი</small>@else<span class="status status-cancelled">არ აქვს</span>@endif</td>
                    <td>@if($registryUser->children_count > 0)<strong>{{ $registryUser->children_count }}</strong><small>{{ $registryUser->children->pluck('first_name')->join(', ') }}</small>@else<span class="status status-cancelled">არ არის დაკავშირებული</span>@endif</td>
                    <td>@if($latestEnrollment)<span class="status status-{{ $latestEnrollment->status }}">{{ \App\Models\Enrollment::STATUSES[$latestEnrollment->status] ?? $latestEnrollment->status }}</span><small>{{ $latestEnrollment->group?->name ?? 'ჯგუფი არ არის' }}</small>@else—@endif</td>
                    <td>@if($clubAccess)<span class="status status-active">დაშვებულია</span>@else<span class="status status-cancelled">დახურულია</span>@endif<small>{{ $registryUser->membershipLabel() }}</small></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">მომხმარებელი ვერ მოიძებნა.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <nav class="pagination">@if($users->onFirstPage())<span>← წინა</span>@else<a href="{{ $users->previousPageUrl() }}">← წინა</a>@endif<strong>{{ $users->currentPage() }} / {{ $users->lastPage() }}</strong>@if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">შემდეგი →</a>@else<span>შემდეგი →</span>@endif</nav>
    @endif
</section>
@endsection
