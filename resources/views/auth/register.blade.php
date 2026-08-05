<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>რეგისტრაცია — ინეს ბაღი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/credentials-auth.css') }}">
</head>
<body>
<main class="credentials-shell credentials-shell-wide">
    <a class="credentials-brand" href="{{ route('home') }}"><i></i><span><strong>ინეს ბაღი</strong><small>მშობლის რეგისტრაცია</small></span></a>

    <section class="credentials-card">
        <span class="credentials-badge">ახალი ანგარიში</span>
        <h1>რეგისტრაცია</h1>
        <p class="credentials-lead">შეავსეთ მშობლისა და ბავშვის ძირითადი ინფორმაცია. რეგისტრაციის შემდეგ მონაცემები ადმინისტრატორს გადაეცემა გადასამოწმებლად.</p>

        @if($errors->any())
            <div class="credentials-errors">
                @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
            </div>
        @endif

        <form class="credentials-form" method="post" action="{{ route('auth.credentials.register') }}">
            @csrf
            <input type="hidden" name="privacy_policy_version" value="{{ $privacyVersion }}">

            <section class="credentials-form-section">
                <div class="credentials-section-heading">
                    <span>01</span>
                    <div><h2>მშობლის ინფორმაცია</h2><p>ეს მონაცემები გამოყენებული იქნება ანგარიშზე შესასვლელად და თქვენთან დასაკავშირებლად.</p></div>
                </div>
                <div class="credentials-field-grid">
                    <label class="credentials-field wide">
                        <span>სახელი და გვარი</span>
                        <input name="name" value="{{ old('name') }}" required minlength="2" maxlength="120" autocomplete="name" autofocus placeholder="მშობლის სახელი და გვარი">
                    </label>
                    <label class="credentials-field">
                        <span>ელფოსტა</span>
                        <input name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" inputmode="email" placeholder="parent@example.com">
                    </label>
                    <label class="credentials-field">
                        <span>მობილურის ნომერი</span>
                        <input name="phone" type="tel" value="{{ old('phone') }}" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="5XX XX XX XX">
                    </label>
                    <label class="credentials-field">
                        <span>პაროლი</span>
                        <input name="password" type="password" required minlength="8" maxlength="128" autocomplete="new-password" placeholder="მინიმუმ 8 სიმბოლო">
                    </label>
                    <label class="credentials-field">
                        <span>გაიმეორეთ პაროლი</span>
                        <input name="password_confirmation" type="password" required minlength="8" maxlength="128" autocomplete="new-password" placeholder="პაროლი ხელახლა">
                    </label>
                </div>
            </section>

            <div class="credentials-divider"></div>

            <section class="credentials-form-section">
                <div class="credentials-section-heading">
                    <span>02</span>
                    <div><h2>ბავშვის ინფორმაცია</h2><p>ბავშვის პროფილი ავტომატურად დაუკავშირდება მშობლის ანგარიშს. ჯგუფსა და ჩარიცხვის სტატუსს ადმინისტრატორი განსაზღვრავს.</p></div>
                </div>
                <div class="credentials-field-grid">
                    <label class="credentials-field">
                        <span>ბავშვის სახელი</span>
                        <input name="child_first_name" value="{{ old('child_first_name') }}" required minlength="2" maxlength="100" autocomplete="off" placeholder="სახელი">
                    </label>
                    <label class="credentials-field">
                        <span>ბავშვის გვარი</span>
                        <input name="child_last_name" value="{{ old('child_last_name') }}" required minlength="2" maxlength="100" autocomplete="off" placeholder="გვარი">
                    </label>
                    <label class="credentials-field wide">
                        <span>დაბადების თარიღი</span>
                        <input name="child_birth_date" type="date" value="{{ old('child_birth_date') }}" required max="{{ now()->format('Y-m-d') }}">
                    </label>
                </div>
            </section>

            <label class="credentials-check">
                <input type="checkbox" name="privacy_accepted" value="1" required @checked(old('privacy_accepted'))>
                <span>გავეცანი <a href="{{ route('privacy') }}" target="_blank" rel="noopener">კონფიდენციალურობის პოლიტიკას</a> და <a href="{{ route('terms') }}" target="_blank" rel="noopener">სარგებლობის პირობებს</a> და ვადასტურებ ბავშვის მონაცემების დამუშავებაზე უფლებამოსილებას.</span>
            </label>
            <button class="credentials-submit" type="submit">რეგისტრაციის დასრულება</button>
        </form>

        <p class="credentials-note">რეგისტრაცია ავტომატურად არ ხსნის მშობელთა კლუბს. ადმინისტრატორს შეუძლია შეამოწმოს და დაარედაქტიროს ბავშვის მონაცემები, მიუჩინოს ჯგუფი და შემდეგ დაამტკიცოს წვდომა.</p>

        <div class="credentials-links">
            <a href="{{ route('auth.credentials.login.form') }}">უკვე გაქვთ ანგარიში?</a>
            <a href="{{ route('home') }}">საიტზე დაბრუნება</a>
        </div>
    </section>
</main>
</body>
</html>
