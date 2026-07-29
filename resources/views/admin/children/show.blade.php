@extends('admin.layout')

@section('title', $child->first_name.' '.$child->last_name)
@section('heading', 'ბავშვის პროფილი')

@section('content')
<section class="admin-section compact">
    <div class="detail-heading">
        <div><a class="back-link" href="{{ route('admin.children.index') }}">← ბავშვების რეესტრი</a><h2>{{ $child->first_name }} {{ $child->last_name }}</h2><p>პროფილი #{{ $child->id }}</p></div>
        <span class="status {{ $child->photo_consent_at ? 'status-approved' : 'status-rejected' }} large">ფოტო: {{ $child->photo_consent_at ? 'თანხმობა მიღებულია' : 'თანხმობა არ არის' }}</span>
    </div>
</section>

<div class="detail-grid">
    <div>
        <section class="admin-section compact panel">
            <div class="panel-heading"><div><p class="eyebrow">კანონიერი წარმომადგენლები</p><h2>მშობლები და მეურვეები</h2></div></div>
            <div class="guardian-grid">
                @forelse($child->guardians as $guardian)
                    <article class="guardian-card"><strong>{{ $guardian->name }}</strong><a href="tel:{{ $guardian->phone }}">{{ $guardian->phone }}</a><small>{{ $guardian->pivot->relationship }} · {{ $guardian->pivot->is_primary ? 'ძირითადი კონტაქტი' : 'დამატებითი კონტაქტი' }} · {{ $guardian->pivot->can_pick_up ? 'შეუძლია გაყვანა' : 'არ შეუძლია გაყვანა' }}</small></article>
                @empty
                    <p class="empty-state">მეურვე დაკავშირებული არ არის.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-section compact panel">
            <div class="panel-heading"><div><p class="eyebrow">Enrollment history</p><h2>ჩარიცხვები და ფინანსები</h2></div></div>
            <div class="enrollment-list">
                @forelse($child->enrollments as $enrollment)
                    <article class="enrollment-card">
                        <div class="enrollment-summary">
                            <div><strong>{{ $enrollment->group?->name ?? 'ჯგუფი წაშლილია' }}</strong><small>{{ $enrollment->starts_on->format('d.m.Y') }} — {{ $enrollment->ends_on?->format('d.m.Y') ?? 'მიმდინარე' }}</small></div>
                            <span class="status status-{{ $enrollment->status }}">{{ $statuses[$enrollment->status] ?? $enrollment->status }}</span>
                        </div>
                        <form class="inline-admin-form" method="post" action="{{ route('admin.enrollments.update', $enrollment) }}">
                            @csrf @method('PATCH')
                            <label><span>სტატუსი</span><select name="status">@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected($enrollment->status===$key)>{{ $label }}</option>@endforeach</select></label>
                            <label><span>დაწყება</span><input type="date" name="starts_on" value="{{ $enrollment->starts_on->format('Y-m-d') }}" required></label>
                            <label><span>დასრულება</span><input type="date" name="ends_on" value="{{ $enrollment->ends_on?->format('Y-m-d') }}"></label>
                            <button class="secondary" type="submit">განახლება</button>
                        </form>
                        <div class="payment-summary">
                            <span>დარიცხვები: <strong>{{ $enrollment->payments->count() }}</strong></span>
                            <span>გადახდილი: <strong>{{ $enrollment->payments->where('status','paid')->sum('amount') }} GEL</strong></span>
                            <span>მოლოდინში: <strong>{{ $enrollment->payments->where('status','pending')->sum('amount') }} GEL</strong></span>
                        </div>
                    </article>
                @empty
                    <p class="empty-state">ჩარიცხვის ისტორია არ არის.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside>
        <section class="admin-section compact panel sticky-panel">
            <div class="panel-heading"><div><p class="eyebrow">პროფილის რედაქტირება</p><h2>ძირითადი მონაცემები</h2></div></div>
            <form class="admin-form" method="post" action="{{ route('admin.children.update', $child) }}">
                @csrf @method('PATCH')
                <label><span>სახელი</span><input name="first_name" value="{{ old('first_name',$child->first_name) }}" required></label>
                <label><span>გვარი</span><input name="last_name" value="{{ old('last_name',$child->last_name) }}"></label>
                <label><span>დაბადების ზუსტი თარიღი</span><input type="date" name="birth_date" value="{{ old('birth_date',$child->birth_date?->format('Y-m-d')) }}"></label>
                <label><span>დაბადების წელი</span><input type="number" min="2018" max="2026" name="birth_year" value="{{ old('birth_year',$child->birth_year) }}"></label>
                <label><span>სამედიცინო/სპეციალური საჭიროებები</span><textarea name="medical_notes" rows="6" maxlength="5000">{{ old('medical_notes',$child->medical_notes) }}</textarea></label>
                <input type="hidden" name="photo_consent" value="0">
                <label class="toggle-row"><input type="checkbox" name="photo_consent" value="1" @checked(old('photo_consent',$child->photo_consent_at ? 1 : 0))><span>ფოტოსა და ვიდეოს გამოქვეყნების თანხმობა</span></label>
                <button class="primary full" type="submit">პროფილის შენახვა</button>
            </form>
        </section>
    </aside>
</div>
@endsection
