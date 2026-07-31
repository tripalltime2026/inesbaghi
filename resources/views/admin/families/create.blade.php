@extends('admin.layout')

@section('title', 'ოჯახის რეგისტრაცია')
@section('heading', 'მშობლისა და ბავშვის დაკავშირება')

@section('content')
<section class="panel" style="padding:24px">
    <p class="admin-kicker">ოჯახის მართვა</p>
    <h2 style="margin:0 0 8px">შექმენით ახალი პროფილები ან დააკავშირეთ არსებული ჩანაწერები</h2>
    <p style="margin:0;color:#667483;line-height:1.7">შეგიძლიათ აირჩიოთ უკვე არსებული მშობელი/ბავშვი, ან ქვემოთ შექმნათ ახალი. ბავშვის ჯგუფში ჩარიცხვა ცალკე დასტურდება.</p>
</section>

<form method="post" action="{{ route('admin.families.store') }}" class="panel" style="padding:24px;margin-top:18px">
    @csrf
    <div class="family-admin-grid">
        <section class="family-admin-card">
            <h2>1. მშობელი</h2>
            <p>აირჩიეთ არსებული ანგარიში ან შექმენით ახალი შესვლის სახელითა და დროებითი პაროლით.</p>
            <label><span>არსებული მშობელი</span><select name="parent_id"><option value="">ახალი მშობლის შექმნა</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((string)old('parent_id')===(string)$parent->id)>{{ $parent->name }} · {{ $parent->username ?: $parent->phone }}</option>@endforeach</select></label>
            <label><span>ახალი მშობლის სახელი და გვარი</span><input name="parent_name" value="{{ old('parent_name') }}"></label>
            <label><span>შესვლის სახელი</span><input name="parent_username" value="{{ old('parent_username') }}" autocomplete="off"></label>
            <label><span>დროებითი პაროლი</span><input name="parent_password" type="password" minlength="8" autocomplete="new-password"></label>
            <label><span>გაიმეორეთ დროებითი პაროლი</span><input name="parent_password_confirmation" type="password" minlength="8" autocomplete="new-password"></label>
            <label><span>მობილურის ნომერი — სურვილის შემთხვევაში</span><input name="parent_phone" value="{{ old('parent_phone') }}" inputmode="tel" placeholder="5XX XX XX XX"></label>
            <label><span>ელფოსტა — სურვილის შემთხვევაში</span><input name="parent_email" value="{{ old('parent_email') }}" type="email"></label>
        </section>

        <section class="family-admin-card">
            <h2>2. ბავშვი</h2>
            <p>აირჩიეთ უკვე არსებული ბავშვის პროფილი ან შექმენით ახალი.</p>
            <label><span>არსებული ბავშვი</span><select name="child_id"><option value="">ახალი ბავშვის შექმნა</option>@foreach($children as $child)<option value="{{ $child->id }}" @selected((string)old('child_id')===(string)$child->id)>{{ $child->first_name }} {{ $child->last_name }} · {{ $child->birth_date?->format('d.m.Y') ?? $child->birth_year }}@if($child->guardians->isNotEmpty()) · {{ $child->guardians->pluck('name')->join(', ') }}@endif</option>@endforeach</select></label>
            <label><span>ბავშვის სახელი</span><input name="child_first_name" value="{{ old('child_first_name') }}"></label>
            <label><span>ბავშვის გვარი</span><input name="child_last_name" value="{{ old('child_last_name') }}"></label>
            <label><span>დაბადების თარიღი</span><input name="child_birth_date" value="{{ old('child_birth_date') }}" type="date" min="2017-01-01" max="{{ now()->toDateString() }}"></label>
            <label><span>კავშირი ბავშვთან</span><select name="relationship" required><option value="">აირჩიეთ</option>@foreach($relationships as $relationship)<option value="{{ $relationship }}" @selected(old('relationship')===$relationship)>{{ $relationship }}</option>@endforeach</select></label>
            <input type="hidden" name="is_primary" value="0"><label class="family-confirm"><input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', true))><span>ეს არის ბავშვის მთავარი საკონტაქტო პირი.</span></label>
            <input type="hidden" name="can_pick_up" value="0"><label class="family-confirm"><input type="checkbox" name="can_pick_up" value="1" @checked(old('can_pick_up', true))><span>მშობელს აქვს ბავშვის გაყვანის უფლება.</span></label>
        </section>
    </div>
    <label class="family-confirm" style="margin-top:20px"><input type="checkbox" name="authority_confirmed" value="1" required><span>ვადასტურებ, რომ მშობლის/კანონიერი წარმომადგენლის ვინაობა და ბავშვთან კავშირი ადმინისტრაციულად გადამოწმებულია.</span></label>
    <div class="family-admin-actions"><button type="submit">პროფილების შექმნა და დაკავშირება</button></div>
</form>
@endsection
