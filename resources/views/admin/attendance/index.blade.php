@extends('admin.layout')

@section('title', 'დასწრება')
@section('heading', 'დასწრება და მოსვლა/წასვლა')

@section('content')
<section class="ops-panel">
    <form class="ops-filters" method="get">
        <label>თარიღი<input type="date" name="date" value="{{ $date }}"></label>
        <label class="grow">ჯგუფი<select name="group_id">@foreach($groups as $group)<option value="{{ $group->id }}" @selected($selectedGroup?->id===$group->id)>{{ $group->name }} — {{ $group->academic_year }}</option>@endforeach</select></label>
        <button class="secondary" type="submit">ჩვენება</button>
    </form>
</section>

<section class="ops-summary-grid">
    <article><span>ჯგუფში</span><strong>{{ $counts['total'] }}</strong><small>{{ $selectedGroup?->name ?? 'ჯგუფი არ არის' }}</small></article>
    <article><span>დასწრებული</span><strong>{{ $counts['present'] }}</strong><small>{{ $date }}</small></article>
    <article><span>გაცდენა</span><strong>{{ $counts['absent'] }}</strong><small>ყველა ტიპის არყოფნა</small></article>
    <article><span>შეუვსებელი</span><strong>{{ $counts['not_recorded'] }}</strong><small>დღის ჩანაწერი აკლია</small></article>
</section>

<section class="ops-panel">
    <div class="ops-panel-head">
        <div><p class="eyebrow">დღიური roster</p><h2>{{ $selectedGroup?->name ?? 'აქტიური ჯგუფი არ მოიძებნა' }}</h2></div>
        <span class="status status-new">{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</span>
    </div>

    <div class="attendance-list">
    @forelse($enrollments as $enrollment)
        @php($child=$enrollment->child)
        @php($record=$child->attendanceRecords->first())
        <article class="attendance-card">
            <div class="attendance-person">
                <div class="child-avatar">{{ mb_substr($child->first_name,0,1) }}</div>
                <div><strong>{{ $child->first_name }} {{ $child->last_name }}</strong><small>{{ $child->guardians->first()?->name }} · {{ $child->guardians->first()?->phone }}</small></div>
            </div>
            <div class="attendance-state">
                <span class="status status-{{ $record?->status==='present'?'approved':($record?'rejected':'new') }}">{{ $record ? (\App\Models\AttendanceRecord::STATUSES[$record->status] ?? $record->status) : 'შეუვსებელი' }}</span>
                <small>მოსვლა: {{ $record?->checked_in_at?->format('H:i') ?? '—' }} · წასვლა: {{ $record?->checked_out_at?->format('H:i') ?? '—' }}</small>
            </div>
            <div class="attendance-actions">
                <form method="post" action="{{ route('admin.attendance.update',$child) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="group_id" value="{{ $selectedGroup->id }}"><input type="hidden" name="action" value="check_in">
                    <button class="secondary compact" type="submit" @disabled($record?->checked_in_at)>მოსვლა</button>
                </form>
                <form class="checkout-form" method="post" action="{{ route('admin.attendance.update',$child) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="group_id" value="{{ $selectedGroup->id }}"><input type="hidden" name="action" value="check_out">
                    <input name="pickup_by_name" value="{{ $record?->pickup_by_name }}" placeholder="ვინ გაიყვანა">
                    <button class="secondary compact" type="submit" @disabled(!$record?->checked_in_at || $record?->checked_out_at)>წასვლა</button>
                </form>
            </div>
            <details class="attendance-details">
                <summary>ჩანაწერის სრულად რედაქტირება</summary>
                <form class="ops-form attendance-edit" method="post" action="{{ route('admin.attendance.update',$child) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="group_id" value="{{ $selectedGroup->id }}"><input type="hidden" name="action" value="save">
                    <label>სტატუსი<select name="status">@foreach(\App\Models\AttendanceRecord::STATUSES as $key=>$label)<option value="{{ $key }}" @selected(($record?->status ?? 'absent')===$key)>{{ $label }}</option>@endforeach</select></label>
                    <label>მოსვლის დრო<input type="time" name="checked_in_time" value="{{ $record?->checked_in_at?->format('H:i') }}"></label>
                    <label>წასვლის დრო<input type="time" name="checked_out_time" value="{{ $record?->checked_out_at?->format('H:i') }}"></label>
                    <label>ვინ გაიყვანა<input name="pickup_by_name" value="{{ $record?->pickup_by_name }}"></label>
                    <label class="full">შენიშვნა<textarea name="note" rows="2">{{ $record?->note }}</textarea></label>
                    <button class="primary" type="submit">შენახვა</button>
                </form>
            </details>
        </article>
    @empty
        <div class="portal-empty large">ამ თარიღზე ჯგუფში აქტიური ბავშვი არ მოიძებნა.</div>
    @endforelse
    </div>
</section>
@endsection
