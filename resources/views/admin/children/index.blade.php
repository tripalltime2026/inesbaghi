@extends('admin.layout')

@section('title', 'ბავშვების რეესტრი')
@section('heading', 'ბავშვების რეესტრი')

@section('content')
<section class="admin-section compact">
    <form class="filter-bar children-filters" method="get" action="{{ route('admin.children.index') }}">
        <label><span>ძიება</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ბავშვი, მშობელი ან ტელეფონი"></label>
        <label><span>ჯგუფი</span><select name="group_id"><option value="">ყველა ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string)($filters['group_id'] ?? '') === (string)$group->id)>{{ $group->name }}</option>@endforeach</select></label>
        <label><span>ჩარიცხვის სტატუსი</span><select name="status"><option value="">ყველა სტატუსი</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(($filters['status'] ?? null)===$key)>{{ $label }}</option>@endforeach</select></label>
        <button class="primary" type="submit">გაფილტვრა</button>
        <a class="text-button" href="{{ route('admin.children.index') }}">გასუფთავება</a>
    </form>
</section>

<section class="admin-section compact">
    <div class="panel-heading"><div><p class="eyebrow">{{ $children->total() }} ბავშვი</p><h2>პროფილები და ჩარიცხვები</h2></div></div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>ბავშვი</th><th>მეურვე</th><th>ჯგუფი</th><th>სტატუსი</th><th>დაწყება</th><th>ფოტოს თანხმობა</th><th></th></tr></thead>
            <tbody>
            @forelse($children as $child)
                @php($enrollment=$child->enrollments->sortByDesc('created_at')->first())
                @php($guardian=$child->guardians->firstWhere('pivot.is_primary', true) ?? $child->guardians->first())
                <tr>
                    <td><strong>{{ $child->first_name }} {{ $child->last_name }}</strong><small>{{ $child->birth_date?->format('d.m.Y') ?? ($child->birth_year ? $child->birth_year.' წელი' : 'დაბადების თარიღი უცნობია') }}</small></td>
                    <td>@if($guardian)<strong>{{ $guardian->name }}</strong><small>{{ $guardian->phone }}</small>@else—@endif</td>
                    <td>{{ $enrollment?->group?->name ?? '—' }}</td>
                    <td>@if($enrollment)<span class="status status-{{ $enrollment->status }}">{{ $statuses[$enrollment->status] ?? $enrollment->status }}</span>@else—@endif</td>
                    <td>{{ $enrollment?->starts_on?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $child->photo_consent_at ? 'მიღებულია' : 'არ არის' }}</td>
                    <td><a class="row-link" href="{{ route('admin.children.show', $child) }}">პროფილი →</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-state">ბავშვი ვერ მოიძებნა.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($children->hasPages())
        <nav class="pagination">@if($children->onFirstPage())<span>← წინა</span>@else<a href="{{ $children->previousPageUrl() }}">← წინა</a>@endif<strong>{{ $children->currentPage() }} / {{ $children->lastPage() }}</strong>@if($children->hasMorePages())<a href="{{ $children->nextPageUrl() }}">შემდეგი →</a>@else<span>შემდეგი →</span>@endif</nav>
    @endif
</section>
@endsection
