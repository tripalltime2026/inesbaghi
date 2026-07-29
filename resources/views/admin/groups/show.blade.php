@extends('admin.layout')

@section('title', $group->name)
@section('heading', $group->name)

@section('content')
<section class="admin-section compact">
    <div class="detail-heading">
        <div><a class="back-link" href="{{ route('admin.groups.index') }}">← ყველა ჯგუფი</a><h2>{{ $group->name }}</h2><p>{{ $group->academic_year }} · {{ $group->age_min_months }}–{{ $group->age_max_months }} თვე</p></div>
        <span class="status {{ $group->is_active ? 'status-approved' : 'status-archived' }} large">{{ $group->is_active ? 'აქტიური ჯგუფი' : 'გამორთული ჯგუფი' }}</span>
    </div>
</section>

<div class="detail-grid">
    <div>
        <section class="admin-section compact panel">
            <div class="panel-heading"><div><p class="eyebrow">Roster</p><h2>ბავშვები ჯგუფში</h2></div><span class="status status-approved">{{ $group->enrollments->where('status','active')->count() }} / {{ $group->capacity }}</span></div>
            <div class="table-wrap">
                <table class="admin-table group-table">
                    <thead><tr><th>ბავშვი</th><th>მეურვე</th><th>სტატუსი</th><th>დაწყება</th><th></th></tr></thead>
                    <tbody>
                    @forelse($group->enrollments as $enrollment)
                        @php($guardian=$enrollment->child?->guardians?->firstWhere('pivot.is_primary',true) ?? $enrollment->child?->guardians?->first())
                        <tr><td><strong>{{ $enrollment->child?->first_name }} {{ $enrollment->child?->last_name }}</strong></td><td>@if($guardian){{ $guardian->name }}<small>{{ $guardian->phone }}</small>@else—@endif</td><td><span class="status status-{{ $enrollment->status }}">{{ \App\Models\Enrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</span></td><td>{{ $enrollment->starts_on->format('d.m.Y') }}</td><td><a class="row-link" href="{{ route('admin.children.show',$enrollment->child) }}">პროფილი →</a></td></tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">ჯგუფში ჩარიცხვა ჯერ არ არის.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <aside>
        <section class="admin-section compact panel sticky-panel">
            <div class="panel-heading"><div><p class="eyebrow">პარამეტრები</p><h2>ჯგუფის მართვა</h2></div></div>
            <form class="admin-form" method="post" action="{{ route('admin.groups.update',$group) }}">
                @csrf @method('PATCH')
                <label><span>დასახელება</span><input name="name" value="{{ old('name',$group->name) }}" required></label>
                <label><span>ადგილების მაქსიმუმი</span><input type="number" name="capacity" min="1" max="100" value="{{ old('capacity',$group->capacity) }}" required></label>
                <label><span>ყოველთვიური გადასახადი (GEL)</span><input type="number" name="monthly_fee" min="0" step="0.01" value="{{ old('monthly_fee',$group->monthly_fee) }}" required></label>
                <label><span>სასწავლო წელი</span><input name="academic_year" pattern="20[0-9]{2}-20[0-9]{2}" value="{{ old('academic_year',$group->academic_year) }}" required></label>
                <input type="hidden" name="is_active" value="0">
                <label class="toggle-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$group->is_active))><span>ჯგუფი აქტიურია და იღებს ჩარიცხვებს</span></label>
                <button class="primary full" type="submit">პარამეტრების შენახვა</button>
            </form>
        </section>
    </aside>
</div>
@endsection
