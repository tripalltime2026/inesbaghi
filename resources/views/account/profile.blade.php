<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>პროფილის შევსება — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/credentials-auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/family-management.css') }}">
</head>
<body>
<main class="credentials-shell">
    <a class="credentials-brand" href="{{ route('home') }}"><i></i><span><strong>ინეს ბაღი</strong><small>ანგარიშის პროფილი</small></span></a>

    <section class="credentials-card">
        <span class="credentials-badge">პროფილის დეტალები</span>
        <h1>მართეთ თქვენი პროფილი და ბავშვები</h1>
        <p class="credentials-lead">შეავსეთ საკონტაქტო ინფორმაცია და დაამატეთ ბავშვი თქვენს ანგარიშზე. ბავშვის დამატება ავტომატურად არ ნიშნავს ჩარიცხვას — ჯგუფსა და აქტიურ სტატუსს ადმინისტრაცია ადასტურებს.</p>

        @if(session('success'))<div class="credentials-success">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="credentials-errors">
                @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
            </div>
        @endif

        <form class="credentials-form" method="post" action="{{ route('account.profile.update') }}">
            @csrf
            @method('PATCH')
            <label class="credentials-field"><span>შესვლის სახელი</span><input name="username" value="{{ old('username', $user->username) }}" required minlength="2" maxlength="80" autocomplete="username"></label>
            <label class="credentials-field"><span>სახელი და გვარი</span><input name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name"></label>
            <label class="credentials-field"><span>მობილურის ნომერი</span><input name="phone" value="{{ old('phone', $user->phone) }}" required inputmode="tel" autocomplete="tel" placeholder="5XX XX XX XX"></label>
            <label class="credentials-field"><span>ელფოსტა — სურვილის შემთხვევაში</span><input name="email" value="{{ old('email', $user->email) }}" type="email" maxlength="190" autocomplete="email"></label>
            <button class="credentials-submit" type="submit">პროფილის შენახვა</button>
        </form>

        <div class="credentials-divider"></div>
        <h2 style="margin:0 0 12px;font:700 23px 'Noto Serif Georgian',serif">{{ $user->password ? 'პაროლის შეცვლა' : 'პაროლის დაყენება' }}</h2>
        <form class="credentials-form" method="post" action="{{ route('account.password.update') }}">
            @csrf
            @method('PATCH')
            @if($user->password)<label class="credentials-field"><span>მიმდინარე პაროლი</span><input name="current_password" type="password" required autocomplete="current-password"></label>@endif
            <label class="credentials-field"><span>ახალი პაროლი</span><input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
            <label class="credentials-field"><span>გაიმეორეთ ახალი პაროლი</span><input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
            <button class="credentials-submit" type="submit">{{ $user->password ? 'პაროლის შეცვლა' : 'პაროლის შენახვა' }}</button>
        </form>

        <section class="family-section" id="children">
            <h2>ჩემი ბავშვები</h2>
            <p>აქ შეგიძლიათ ბავშვის პროფილის შექმნა და თქვენს ანგარიშთან დაკავშირება.</p>
            <div class="family-list">
                @forelse($children as $child)
                    @php($enrollment = $child->enrollments->sortByDesc('created_at')->first())
                    <article class="family-child-card">
                        <div><strong>{{ $child->first_name }} {{ $child->last_name }}</strong><span>{{ $child->birth_date?->format('d.m.Y') ?? $child->birth_year }} · {{ $child->pivot->relationship }}</span></div>
                        <span class="family-tag">{{ $enrollment?->group?->name ?? 'ჯგუფი არ არის მინიჭებული' }}</span>
                    </article>
                @empty
                    <div class="family-note">თქვენს ანგარიშზე ბავშვი ჯერ არ არის დამატებული.</div>
                @endforelse
            </div>

            <h2>ბავშვის დამატება</h2>
            <form class="credentials-form" method="post" action="{{ route('account.children.store') }}">
                @csrf
                <div class="family-form-grid">
                    <label class="credentials-field"><span>ბავშვის სახელი</span><input name="first_name" value="{{ old('first_name') }}" required maxlength="100"></label>
                    <label class="credentials-field"><span>ბავშვის გვარი</span><input name="last_name" value="{{ old('last_name') }}" maxlength="100"></label>
                    <label class="credentials-field"><span>დაბადების თარიღი</span><input name="birth_date" value="{{ old('birth_date') }}" type="date" min="2017-01-01" max="{{ now()->toDateString() }}" required></label>
                    <label class="credentials-field"><span>თქვენი კავშირი ბავშვთან</span><select name="relationship" required><option value="">აირჩიეთ</option>@foreach(['დედა','მამა','მშობელი','კანონიერი წარმომადგენელი'] as $relationship)<option value="{{ $relationship }}" @selected(old('relationship')===$relationship)>{{ $relationship }}</option>@endforeach</select></label>
                    <input type="hidden" name="can_pick_up" value="0">
                    <label class="family-confirm wide"><input type="checkbox" name="can_pick_up" value="1" @checked(old('can_pick_up', true))><span>მე მაქვს ბავშვის ბაღიდან გაყვანის უფლება.</span></label>
                    <label class="family-confirm wide"><input type="checkbox" name="guardian_confirmation" value="1" required><span>{{ $guardianConfirmation }}</span></label>
                </div>
                <button class="credentials-submit" type="submit">ბავშვის პროფილის შექმნა</button>
            </form>
            <div class="family-note" style="margin-top:14px">ჯანმრთელობის ინფორმაცია და სხვა განსაკუთრებული მონაცემები ამ ფორმით არ გროვდება. საჭირო დეტალებს ადმინისტრაცია ცალკე, დაცული პროცესით მოგთხოვთ.</div>
        </section>

        <div class="profile-actions">
            <a href="{{ route('account.status') }}">ანგარიშის სტატუსი</a>
            <a href="{{ route('home') }}">საჯარო საიტი</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">გასვლა</button></form>
        </div>
    </section>
</main>
</body>
</html>
