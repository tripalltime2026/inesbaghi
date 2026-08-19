@extends('admin.layout')

@section('title', 'გადახდები')
@section('heading', 'გადახდები და დავალიანება')

@section('content')
<section class="ops-summary-grid">
    <article><span>დარიცხულია</span><strong>{{ number_format($summary['charged'], 2) }} GEL</strong><small>{{ $period }}</small></article>
    <article><span>გადახდილია</span><strong>{{ number_format($summary['paid'], 2) }} GEL</strong><small>დაფიქსირებული ტრანზაქციები</small></article>
    <article><span>დარჩენილია</span><strong>{{ number_format($summary['outstanding'], 2) }} GEL</strong><small>მიმდინარე დავალიანება</small></article>
    <article><span>დადასტურება</span><strong>{{ $summary['confirmed_count'] }} / {{ $summary['confirmed_count'] + $summary['draft_count'] }}</strong><small>{{ $summary['draft_count'] }} ჯერ დასადასტურებელია</small></article>
</section>

<section class="ops-panel">
    <div class="ops-panel-head">
        <div><p class="eyebrow">1. შექმნა</p><h2>თვიური დარიცხვების მომზადება</h2><p>თითო აქტიურ ბავშვზე იქმნება ცალკე დარიცხვა. თანხა ავტომატურად იღებს ჯგუფის თვიურ საფასურს, თუმცა დადასტურებამდე შეგიძლიათ კონკრეტული ბავშვის თანხა შეცვალოთ.</p></div>
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
    <div class="ops-panel-head">
        <div><p class="eyebrow">2. დადასტურება</p><h2>თვის დარიცხვების გამოქვეყნება მშობლებთან</h2><p>მშობელი ხედავს მხოლოდ დადასტურებულ დარიცხვებს და მხოლოდ საკუთარ ბავშვებზე. შეგიძლიათ ჯერ გადაამოწმოთ თანხები და შემდეგ ერთ მოქმედებაში დაადასტუროთ მთელი თვე ან კონკრეტული ჯგუფი.</p></div>
        <span class="status {{ $summary['draft_count'] > 0 ? 'status-new' : 'status-approved' }}">{{ $summary['draft_count'] > 0 ? $summary['draft_count'].' დასადასტურებელი' : 'ყველაფერი დადასტურებულია' }}</span>
    </div>
    <form class="ops-form-inline" method="post" action="{{ route('admin.payments.confirm-period') }}" onsubmit="return confirm('დადასტურების შემდეგ ამ თვის დარიცხვები მშობლების პირად კაბინეტში გამოჩნდება. გავაგრძელოთ?')">
        @csrf
        <input type="hidden" name="period" value="{{ $period }}">
        <label>ჯგუფი<select name="group_id"><option value="">ყველა ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
        <button class="primary" type="submit">{{ $period }} — დადასტურება</button>
    </form>
</section>

<section class="ops-panel">
    <form class="ops-filters" method="get">
        <label>პერიოდი<input type="month" name="period" value="{{ $period }}"></label>
        <label>დადასტურება<select name="confirmation"><option value="">ყველა</option><option value="draft" @selected(($filters['confirmation'] ?? '')==='draft')>დასადასტურებელი</option><option value="confirmed" @selected(($filters['confirmation'] ?? '')==='confirmed')>დადასტურებული</option></select></label>
        <label>სტატუსი<select name="status"><option value="">ყველა სტატუსი</option>@foreach(\App\Models\Payment::STATUSES as $key=>$label)<option value="{{ $key }}" @selected(($filters['status'] ?? '')===$key)>{{ $label }}</option>@endforeach</select></label>
        <label>ჯგუფი<select name="group_id"><option value="">ყველა ჯგუფი</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string)($filters['group_id'] ?? '')===(string)$group->id)>{{ $group->name }}</option>@endforeach</select></label>
        <label class="grow">ძიება<input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ბავშვი, მშობელი ან ტელეფონი"></label>
        <button class="secondary" type="submit">გაფილტვრა</button>
    </form>

    <div class="ops-table-wrap">
        <table class="ops-table">
            <thead><tr><th>ბავშვი / მშობელი</th><th>ჯგუფი</th><th>პერიოდი</th><th>დარიცხვა</th><th>გადახდილი</th><th>დარჩენილი</th><th>დადასტურება</th><th></th></tr></thead>
            <tbody>
            @forelse($payments as $payment)
                @php($child=$payment->enrollment?->child)
                @php($guardian=$child?->guardians?->first())
                @php($effective=$payment->effectiveStatus())
                <tr>
                    <td><strong>{{ $child?->first_name }} {{ $child?->last_name }}</strong><small>{{ $guardian?->name }} · {{ $guardian?->phone }}</small></td>
                    <td>{{ $payment->enrollment?->group?->name ?? '—' }}</td>
                    <td><strong>{{ $payment->period }}</strong><small>{{ $payment->period_starts_on?->format('d.m.Y') ?? '—' }} — {{ $payment->period_ends_on?->format('d.m.Y') ?? '—' }}<br>ვადა: {{ $payment->due_at?->format('d.m.Y') ?? '—' }}</small></td>
                    <td><strong>{{ number_format($payment->totalDue(),2) }} {{ $payment->currency }}</strong>@if((float)$payment->discount_amount>0)<small>ფასდაკლება: {{ number_format((float)$payment->discount_amount,2) }}</small>@endif</td>
                    <td>{{ number_format((float)$payment->paid_amount,2) }} {{ $payment->currency }}</td>
                    <td><strong>{{ number_format($payment->outstandingAmount(),2) }} {{ $payment->currency }}</strong><small>{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</small></td>
                    <td>@if($payment->isConfirmed())<span class="status status-approved">დადასტურებული</span><small>{{ $payment->confirmed_at?->format('d.m.Y H:i') }}</small>@else<span class="status status-new">დასადასტურებელი</span>@endif</td>
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
