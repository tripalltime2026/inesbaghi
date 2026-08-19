@extends('admin.layout')

@section('title', 'ჩარიცხვის განაცხადები')
@section('heading', 'ჩარიცხვის განაცხადები')

@section('content')
<section class="admin-section compact">
    <div class="pipeline-grid">
        @foreach ($statuses as $key => $label)
            <a class="pipeline-card {{ ($filters['status'] ?? null) === $key ? 'active' : '' }}" href="{{ route('admin.admissions.index', ['status' => $key]) }}">
                <span>{{ $label }}</span>
                <strong>{{ $statusCounts[$key] ?? 0 }}</strong>
            </a>
        @endforeach
    </div>
</section>

<section class="admin-section compact">
    <form class="filter-bar" method="get" action="{{ route('admin.admissions.index') }}">
        <label>
            <span>ძიება</span>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="მშობელი, ტელეფონი ან ბავშვის სახელი">
        </label>
        <label>
            <span>სტატუსი</span>
            <select name="status">
                <option value="">ყველა სტატუსი</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>პასუხისმგებელი</span>
            <select name="assigned_to">
                <option value="">ყველა თანამშრომელი</option>
                @foreach ($assignableUsers as $user)
                    <option value="{{ $user->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>ტური</span>
            <select name="tour">
                <option value="">ყველა</option>
                <option value="today" @selected(($filters['tour'] ?? null) === 'today')>დღეს</option>
                <option value="upcoming" @selected(($filters['tour'] ?? null) === 'upcoming')>დაგეგმილი</option>
                <option value="requested" @selected(($filters['tour'] ?? null) === 'requested')>ტური მოთხოვნილია</option>
            </select>
        </label>
        <button class="primary" type="submit">გაფილტვრა</button>
        <a class="text-button" href="{{ route('admin.admissions.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="admin-section compact">
    <div class="panel-heading">
        <div><p class="eyebrow">{{ $applications->total() }} ჩანაწერი</p><h2>განაცხადების ბაზა</h2></div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
            <tr><th>#</th><th>მშობელი / ბავშვი</th><th>ჯგუფი</th><th>ტური</th><th>სტატუსი</th><th>პასუხისმგებელი</th><th>შემოსულია</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($applications as $application)
                <tr>
                    <td>{{ $application->id }}</td>
                    <td><strong>{{ $application->parent_name }}</strong><small>{{ $application->phone }} · {{ $application->child_name ?: 'ბავშვის მონაცემები დასაზუსტებელია' }}</small></td>
                    <td>{{ $application->preferred_group }} წელი<small>{{ $application->academic_year }} სასწ. წელი</small></td>
                    <td>
                        @if ($application->tour_scheduled_at)
                            <strong>{{ $application->tour_scheduled_at->format('d.m.Y H:i') }}</strong>
                        @elseif ($application->wants_tour)
                            <span class="status status-new">მოთხოვნილია</span>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="status status-{{ $application->status }}">{{ $statuses[$application->status] ?? $application->status }}</span></td>
                    <td>{{ $application->assignedTo?->name ?? '—' }}</td>
                    <td>{{ $application->created_at->format('d.m.Y H:i') }}</td>
                    <td>
                        <a class="row-link" href="{{ route('admin.admissions.show', $application) }}">გახსნა →</a>
                        <form method="post" action="{{ route('admin.admissions.update', $application) }}" style="display:inline" onsubmit="return confirm('ნამდვილად გსურთ ამ განცხადების წაშლა? ეს მოქმედება ვერ გაუქმდება.')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="intent" value="delete">
                            <button class="danger-button" type="submit" style="margin-left:8px">წაშლა</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-state">ამ ფილტრებით განაცხადი ვერ მოიძებნა.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($applications->hasPages())
        <nav class="pagination" aria-label="გვერდები">
            @if ($applications->onFirstPage())<span>← წინა</span>@else<a href="{{ $applications->previousPageUrl() }}">← წინა</a>@endif
            <strong>{{ $applications->currentPage() }} / {{ $applications->lastPage() }}</strong>
            @if ($applications->hasMorePages())<a href="{{ $applications->nextPageUrl() }}">შემდეგი →</a>@else<span>შემდეგი →</span>@endif
        </nav>
    @endif
</section>
@endsection
