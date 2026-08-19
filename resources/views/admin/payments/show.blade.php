@extends('admin.layout')

@section('title', 'დარიცხვა #'.$payment->id)
@section('heading', 'დარიცხვა #'.$payment->id)

@section('content')
@php($child=$payment->enrollment?->child)
@php($guardian=$child?->guardians?->first())
@php($effective=$payment->effectiveStatus())

<p><a class="table-link" href="{{ route('admin.payments.index',['period'=>$payment->period]) }}">← გადახდების სიაში დაბრუნება</a></p>

<section class="ops-summary-grid">
    <article><span>სრული დარიცხვა</span><strong>{{ number_format((float)$payment->amount,2) }} {{ $payment->currency }}</strong><small>{{ $payment->period }}</small></article>
    <article><span>ფასდაკლება</span><strong>{{ number_format((float)$payment->discount_amount,2) }} {{ $payment->currency }}</strong><small>საბოლოო: {{ number_format($payment->totalDue(),2) }}</small></article>
    <article><span>გადახდილია</span><strong>{{ number_format((float)$payment->paid_amount,2) }} {{ $payment->currency }}</strong><small>{{ $payment->transactions->count() }} ტრანზაქცია</small></article>
    <article><span>დარჩენილი</span><strong>{{ number_format($payment->outstandingAmount(),2) }} {{ $payment->currency }}</strong><small>{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</small></article>
</section>

@if(!$payment->isConfirmed() && $payment->status !== 'cancelled')
<section class="ops-panel">
    <div class="ops-panel-head"><div><p class="eyebrow">დადასტურება</p><h2>დარიცხვა ჯერ მშობელს არ უჩანს</h2><p>ჯერ გადაამოწმეთ ბავშვის თანხა, მომსახურების პერიოდი და გადახდის ვადა. შემდეგ დაადასტურეთ.</p></div><span class="status status-new">დასადასტურებელი</span></div>
    <form method="post" action="{{ route('admin.payments.confirm',$payment) }}" onsubmit="return confirm('ამ დარიცხვის დადასტურების შემდეგ მშობელი მას პირად კაბინეტში დაინახავს. გავაგრძელოთ?')">
        @csrf @method('PATCH')
        <button class="primary" type="submit">დარიცხვის დადასტურება</button>
    </form>
</section>
@endif

<div class="ops-two-column">
    <section class="ops-panel">
        <div class="ops-panel-head"><div><p class="eyebrow">ოჯახი</p><h2>{{ $child?->first_name }} {{ $child?->last_name }}</h2></div><span class="status status-{{ in_array($effective,['paid','waived'],true)?'approved':($effective==='overdue'?'rejected':'new') }}">{{ \App\Models\Payment::STATUSES[$effective] ?? $effective }}</span></div>
        <dl class="ops-definition-list">
            <div><dt>მშობელი</dt><dd>{{ $guardian?->name ?? '—' }}</dd></div>
            <div><dt>ტელეფონი</dt><dd>{{ $guardian?->phone ?? '—' }}</dd></div>
            <div><dt>ჯგუფი</dt><dd>{{ $payment->enrollment?->group?->name ?? '—' }}</dd></div>
            <div><dt>თვე</dt><dd>{{ $payment->period }}</dd></div>
            <div><dt>მომსახურების პერიოდი</dt><dd>{{ $payment->period_starts_on?->format('d.m.Y') ?? '—' }} — {{ $payment->period_ends_on?->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt>გადახდის ვადა</dt><dd>{{ $payment->due_at?->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt>დადასტურება</dt><dd>{{ $payment->confirmed_at ? $payment->confirmed_at->format('d.m.Y H:i').' · '.($payment->confirmedBy?->name ?? 'ადმინისტრატორი') : 'ჯერ არ არის დადასტურებული' }}</dd></div>
            <div><dt>შემქმნელი</dt><dd>{{ $payment->issuedBy?->name ?? 'ავტომატური სისტემა' }}</dd></div>
        </dl>

        <form class="ops-form" method="post" action="{{ route('admin.payments.update',$payment) }}">
            @csrf @method('PATCH')
            <label>ბავშვის თვიური თანხა (GEL)<input type="number" min="0" step="0.01" name="amount" value="{{ old('amount',$payment->amount) }}" required><small>აქ შეგიძლიათ დააყენოთ სხვა თანხა, თუ ოჯახის/ბავშვის პირობები განსხვავებულია.</small></label>
            <label>ფასდაკლება (GEL)<input type="number" min="0" step="0.01" name="discount_amount" value="{{ old('discount_amount',$payment->discount_amount) }}" required></label>
            <label>პერიოდის დასაწყისი<input type="date" name="period_starts_on" value="{{ old('period_starts_on',$payment->period_starts_on?->format('Y-m-d')) }}" required></label>
            <label>პერიოდის დასასრული<input type="date" name="period_ends_on" value="{{ old('period_ends_on',$payment->period_ends_on?->format('Y-m-d')) }}" required></label>
            <label>გადახდის ვადა<input type="date" name="due_at" value="{{ old('due_at',$payment->due_at?->format('Y-m-d')) }}" required></label>
            <label>მდგომარეობა<select name="status" required><option value="pending" @selected(in_array($payment->status,['pending','partial','overdue','paid'],true))>აქტიური დარიცხვა</option><option value="waived" @selected($payment->status==='waived')>ჩამოწერილი</option><option value="cancelled" @selected($payment->status==='cancelled')>გაუქმებული</option></select></label>
            <label class="full">შიდა შენიშვნა<textarea name="notes" rows="4">{{ old('notes',$payment->notes) }}</textarea></label>
            <button class="secondary" type="submit">დარიცხვის განახლება</button>
        </form>
    </section>

    <section class="ops-panel">
        <div class="ops-panel-head"><div><p class="eyebrow">ახალი ტრანზაქცია</p><h2>გადახდის დაფიქსირება</h2></div></div>
        @if(!$payment->isConfirmed())
            <div class="portal-empty">გადახდის დაფიქსირებამდე ჯერ დაადასტურეთ ამ თვის დარიცხვა.</div>
        @elseif($payment->outstandingAmount()>0 && !in_array($payment->status,['waived','cancelled'],true))
            <form class="ops-form" method="post" action="{{ route('admin.payments.transactions.store',$payment) }}">
                @csrf
                <label>თანხა (GEL)<input type="number" name="amount" min="0.01" max="{{ $payment->outstandingAmount() }}" step="0.01" value="{{ old('amount',$payment->outstandingAmount()) }}" required></label>
                <label>მეთოდი<select name="method" required>@foreach(\App\Models\PaymentTransaction::METHODS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                <label>გადახდის დრო<input type="datetime-local" name="paid_at" value="{{ old('paid_at',now()->format('Y-m-d\TH:i')) }}" required></label>
                <label>რეფერენსი<input name="reference" value="{{ old('reference') }}" placeholder="ბანკის ან ქვითრის ნომერი"></label>
                <label class="full">შენიშვნა<textarea name="note" rows="3">{{ old('note') }}</textarea></label>
                <button class="primary" type="submit">გადახდის დამატება</button>
            </form>
        @else
            <div class="portal-empty">ამ დარიცხვაზე ახალი გადახდა აღარ არის საჭირო ან დარიცხვა დახურულია.</div>
        @endif
    </section>
</div>

<section class="ops-panel">
    <div class="ops-panel-head"><div><p class="eyebrow">ისტორია</p><h2>გადახდის ტრანზაქციები</h2></div></div>
    <div class="ops-table-wrap">
        <table class="ops-table">
            <thead><tr><th>თარიღი</th><th>თანხა</th><th>მეთოდი</th><th>რეფერენსი</th><th>დააფიქსირა</th><th>შენიშვნა</th></tr></thead>
            <tbody>
            @forelse($payment->transactions as $transaction)
                <tr><td>{{ $transaction->paid_at->format('d.m.Y H:i') }}</td><td><strong>{{ number_format((float)$transaction->amount,2) }} GEL</strong></td><td>{{ \App\Models\PaymentTransaction::METHODS[$transaction->method] ?? $transaction->method }}</td><td>{{ $transaction->reference ?? '—' }}</td><td>{{ $transaction->recordedBy?->name ?? 'სისტემა' }}</td><td>{{ $transaction->note ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="6" class="empty-cell">გადახდა ჯერ არ დაფიქსირებულა.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
