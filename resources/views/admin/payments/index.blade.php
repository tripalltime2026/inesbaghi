@extends('admin.layout')

@section('title', 'გადახდები')
@section('heading', 'გადახდები და დავალიანება')

@section('content')
<section class="ops-summary-grid">
    <article><span>დარიცხულია</span><strong>{{ number_format($summary['charged'], 2) }} GEL</strong><small>{{ $period }}</small></article>
    <article><span>გადახდილია</span><strong>{{ number_format($summary['paid'], 2) }} GEL</strong><small>დაფიქსირებული ტრანზაქციები</small></article>
    <article><span>დარჩენილია</span><strong>{{ number_format($summary['outstanding'], 2) }} GEL</strong><small>მიმდინარე დავალიანება</small></article>
    <article><span>ვადაგადაცილებული</span><strong>{{ $summary['overdue_count'] }}</strong><small>დარიცხვა</small></article>
</section>

<section class="ops-panel">
    <div class="ops-panel-head">
        <div><p class="eyebrow">ავტომატიზაცია</p><h2>თვიური დარიცხვების შექმნა</h2></div>
        <span class="status status-approved">დუბლიკატებისგან დაცული</span>
    </div>
    <form class="ops-form-inline" method="post" action="{{ route('admin.payments.generate') }}">
        @csrf
        <label>პერიოდი<input type="month" name="period" value="{{ $period }}" required></label>
        <label>გადახდის ვადა<input type="date" name="due_at" value="{{ \Carbon\Carbon::parse($period.'-10')->format('Y-m-d') }}" required></label>
        <label>ჯგუფი<select name="group_id"><option value="">ყველა აქტიური ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
        <button class="primary" type="submit">დარიცხვების შექმნა</button>
    </form>
</section>

<section class="ops-panel">
    <form class="ops-filters" method="get">
        <label>პერიოდი<input type="month" name="period" value="{{ $period }}"></label>
        <label>სტატუსი<select name="status"><option value="">ყველა სტატუსი</option>@foreach(\App\Models\Payment::STATUSES as $key=>$label)<option value="{{ $key }}" @selected(($filters['status'] ?? '')===$key)>{{ $label }}</option>@endforeach</select></label>
        <label>ჯგუფი<select name="group_id"><option value="">ყველა ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string)($filters['group_id'] ?? '')===(string)$group->id)>{{ $group->name }}</option>@endforeach</select></label>
        <label class="grow">ძიება<input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ბავშვი, მშობელი ან ტელეფონი"></label>
        <button class="secondary" type="submit">გაფილტვრა</button>
    </form>

    <div class="ops-table-wrap">
        <table class="ops-table">
            <thead><tr><th>ბავშვი / მშობელი</th><th>ჯგუფი</th><th>დარიცხვა</th><th>გადახდილი</th><th>დარჩენილი</th><th>ვადა</th><th>სტატუსი</th><th></th></tr></thead>
            <tbody>
            @forelse($payments as $payment)
                @php($child=$payment->enrollment?->child)
                @php($guardian=$child?->guardians?->first())
                @php($effective=$payment->effectiveStatus())
                <tr>
                    <td><strong>{{ $child?->first_name }} {{ $child?->last_name }}</strong><small>{{ $guardian?->name }} · {{ $guardian?->phone }}</small></td>
                    <td>{{ $payment->enrollment?->group?->name ?? '—' }}</td>
                    <td><strong>{{ number_format($payment->totalDue(),2) }} {{ $payment->currency }}</strong>@if((float)$payment->discount_amount>0)<small>ფასდაკლება: {{ number_format((float)$payment->discount_amount,2) }}</small>@endif</td>
                    <td>{{ number_format((float)$payment->paid_amount,2) }} {{ $payment->currency }}</td>
                    <td><strong>{{ number_format($payment->outstandingAmount(),2) }} {{ $payment->currency }}</strong></td>
                    <td>{{ $payment->due_at?->format('d.m.Y') ?? '—' }}</td>
                    <td><span class="status status-{{ in_array($effective,['paid','waived'],true)?'approved':($effective==='overdue'?'rejected':'new') }}">{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</span></td>
                    <td><a class="table-link" href="{{ route('admin.payments.show',$payment) }}">გახსნა</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-cell">ამ ფილტრებით დარიცხვა არ მოიძებნა.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</section>
@endsection
