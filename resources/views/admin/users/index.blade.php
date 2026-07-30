@extends('admin.layout')

@section('title', 'მომხმარებელთა რეესტრი')
@section('heading', 'მომხმარებელთა რეესტრი')

@section('content')
<section class="stats-grid compact-stats">
    <article><span>რეგისტრირებული ანგარიშები</span><strong>{{ $stats['total'] }}</strong><small>member და parent ანგარიშები</small></article>
    <article><span>დადასტურებული მშობლები</span><strong>{{ $stats['verified'] }}</strong><small>აქტიური ბავშვის ჩარიცხვით</small></article>
    <article><span>კლუბზე წვდომის გარეშე</span><strong>{{ $stats['without_access'] }}</strong><small>რეგისტრაცია არ უდრის მშობლის სტატუსს</small></article>
    <article><span>ჩარიცხვა დასამტკიცებელია</span><strong>{{ $stats['pending_enrollment'] }}</strong><small>ჯერ არ აქვს კლუბის წვდომა</small></article>
</section>

<section class="admin-section compact">
    <div class="panel-heading">
        <div><p class="eyebrow">წვდომის წესი</p><h2>კლუბი მხოლოდ აქტიური ჩარიცხვის შემდეგ</h2></div>
    </div>
    <p class="section-help">ანგარიშის შექმნა, ტელეფონის დადასტურება ან განაცხადის შევსება თავისთავად არ ნიშნავს, რომ მომხმარებელი ბაღის მშობელია. კლუბზე წვდომა ავტომატურად ენიჭება მხოლოდ მაშინ, როცა მომხმარებელი დაკავშირებულია ბავშვთან და ბავშვის enrollment არის <strong>აქტიური</strong>. ჩარიცხვის დასრულების, შეჩერების ან გაუქმებისას წვდომა ავტომატურად იხურება.</p>
</section>

<section class="admin-section compact">
    <form class="filter-bar" method="get" action="{{ route('admin.users.index') }}">
        <label><span>ძიება</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="სახელი, ტელეფონი ან ელფოსტა"></label>
        <label><span>კლუბის სტატუსი</span><select name="access">
            <option value="">ყველა ანგარიში</option>
            <option value="verified" @selected(($filters['access'] ?? null)==='verified')>დადასტურებული მშობელი</option>
            <option value="without_access" @selected(($filters['access'] ?? null)==='without_access')>კლუბზე წვდომის გარეშე</option>
            <option value="pending_enrollment" @selected(($filters['access'] ?? null)==='pending_enrollment')>ჩარიცხვა დასამტკიცებელია</option>
        </select></label>
        <button class="primary" type="submit">გაფილტვრა</button>
        <a class="text-button" href="{{ route('admin.users.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">{{ $users->total() }} ანგარიში</p><h2>რეგისტრაცია, განაცხადი და მშობლის სტატუსი</h2></div></div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>მომხმარებელი</th><th>რეგისტრაცია</th><th>ბოლო განაცხადი</th><th>ბავშვი და ჩარიცხვა</th><th>კლუბის წვდომა</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $user)
                @php($application = $applications->get($user->phone))
                @php($enrollments = $user->children->flatMap(fn($child) => $child->enrollments))
                @php($activeEnrollments = $enrollments->where('status', 'active'))
                @php($pendingEnrollments = $enrollments->where('status', 'pending'))
                @php($hasAccess = $user->hasVerifiedParentAccess())
                <tr>
                    <td><strong>{{ $user->name }}</strong><small>{{ $user->phone }}</small><small>როლი: {{ $user->role === 'parent' ? 'მშობელი' : 'რეგისტრირებული მომხმარებელი' }}</small></td>
                    <td><strong>{{ $user->phone_verified_at ? 'ტელეფონი დადასტურებულია' : 'ტელეფონი დაუდასტურებელია' }}</strong><small>{{ $user->created_at?->format('d.m.Y H:i') }}</small></td>
                    <td>
                        @if($application)
                            <strong>#{{ $application->id }} · {{ $applicationStatuses[$application->status] ?? $application->status }}</strong>
                            <small>{{ $application->child_name }} · {{ $application->created_at?->format('d.m.Y') }}</small>
                        @else
                            <strong>განაცხადი არ არის</strong><small>ანგარიში შეიქმნა განაცხადის გარეშე</small>
                        @endif
                    </td>
                    <td>
                        @if($user->children->isEmpty())
                            <strong>ბავშვი არ არის დაკავშირებული</strong>
                        @else
                            <strong>{{ $user->children->map(fn($child) => trim($child->first_name.' '.$child->last_name))->join(', ') }}</strong>
                            @if($activeEnrollments->isNotEmpty())
                                <small>აქტიური: {{ $activeEnrollments->map(fn($enrollment) => $enrollment->group?->name)->filter()->unique()->join(', ') }}</small>
                            @elseif($pendingEnrollments->isNotEmpty())
                                <small>დასამტკიცებელი: {{ $pendingEnrollments->map(fn($enrollment) => $enrollment->group?->name)->filter()->unique()->join(', ') }}</small>
                            @else
                                <small>აქტიური ჩარიცხვა არ არის</small>
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($hasAccess)
                            <span class="status status-active">დადასტურებული მშობელი</span><small>კლუბი და ჯგუფური ფორუმი ღიაა</small>
                        @else
                            <span class="status status-pending">წვდომა დახურულია</span><small>{{ $pendingEnrollments->isNotEmpty() ? 'ჩარიცხვის გააქტიურებას ელოდება' : 'აქტიური enrollment არ აქვს' }}</small>
                        @endif
                    </td>
                    <td>
                        @if($application)<a class="row-link" href="{{ route('admin.admissions.show', $application) }}">განაცხადი →</a>@elseif($user->children->isNotEmpty())<a class="row-link" href="{{ route('admin.children.show', $user->children->first()) }}">ბავშვი →</a>@else—@endif
                    </td>
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
