<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>ინეს ბაღი — საბავშვო ბაღი</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header><a class="brand" href="#home"><span class="logo">ი</span><span><b>ინეს ბაღი</b><small>კერძო საბავშვო ბაღი</small></span></a><nav><a href="#about">ჩვენ შესახებ</a><a href="#groups">ჯგუფები</a><a href="#admission">ჩარიცხვა</a></nav><button class="login" data-open-login>შესვლა</button></header>
<main>
<section class="hero" id="home"><div><span class="badge">🌈 უსაფრთხო და მზრუნველი გარემო</span><h1>სახლი, სადაც ბავშვი იზრდება სიყვარულითა და ყურადღებით</h1><p>თამაშზე დაფუძნებული სწავლება, პატარა ჯგუფები, გამოცდილი გუნდი და მშობელთან მუდმივი კავშირი.</p><div class="actions"><a class="primary" href="#admission">ჩარიცხვის განაცხადი</a><button class="secondary" data-open-login>მშობელთა კლუბი</button></div></div><div class="visual"><div class="sun">☀</div><div class="house">ინეს<br><small>ბაღი</small></div></div></section>
<section id="about"><div class="section-head"><span>ჩვენი მიდგომა</span><h2>ბავშვის განვითარება, უსაფრთხოება და დამოუკიდებლობა</h2></div><div class="cards"><article><b>🧩 მონტესორის პრინციპები</b><p>სწავლა მოქმედებით, არჩევანის თავისუფლება და ასაკზე მორგებული გარემო.</p></article><article><b>👩‍🏫 პატარა ჯგუფები</b><p>თითოეულ ბავშვზე მეტი ყურადღება და ყოველდღიური უკუკავშირი მშობელთან.</p></article><article><b>🌿 ჯანსაღი რიტმი</b><p>კვება, მოძრაობა, დასვენება, შემოქმედება და ემოციური მხარდაჭერა.</p></article></div></section>
<section id="groups" class="tint"><div class="section-head"><span>ასაკობრივი ჯგუფები</span><h2>თითო ასაკს — საკუთარი პროგრამა</h2></div><div class="groups">@foreach(['2-3'=>'პირველი ნაბიჯები','3-4'=>'დამოუკიდებლობა','4-5'=>'აღმოჩენები','5-6'=>'სკოლისთვის მზადება'] as $age=>$name)<article><strong>{{ $age }} წელი</strong><h3>{{ $name }}</h3><p>20-მდე ადგილი · ინდივიდუალური მიდგომა</p></article>@endforeach</div></section>
<section id="admission"><div class="section-head"><span>ჩარიცხვა</span><h2>დაგვიტოვეთ განაცხადი</h2></div><form id="admission-form" class="form"><input name="parent_name" placeholder="მშობლის სახელი და გვარი" required><input name="phone" placeholder="ტელეფონი: 5XX XX XX XX" required><input name="child_name" placeholder="ბავშვის სახელი" required><input name="birth_year" type="number" min="2018" max="2026" placeholder="დაბადების წელი"><select name="preferred_group"><option value="2-3">2-3 წელი</option><option value="3-4" selected>3-4 წელი</option><option value="4-5">4-5 წელი</option><option value="5-6">5-6 წელი</option></select><select name="academic_year"><option value="2026">2026-2027</option><option value="2027">2027-2028</option></select><label class="check"><input name="wants_tour" type="checkbox" checked> მსურს ბაღის ტურის დაჯავშნა</label><input name="preferred_tour_date" type="date"><textarea name="comment" placeholder="კომენტარი"></textarea><button class="primary" type="submit">განაცხადის გაგზავნა</button><p class="form-message" aria-live="polite"></p></form></section>
</main>
<footer>© 2026 ინეს ბაღი · თბილისი</footer>
<dialog id="login-dialog"><button class="close" data-close-login>×</button><div id="login-step-one"><h2>შესვლა / რეგისტრაცია</h2><p>ტელეფონზე გამოგიგზავნით 6-ნიშნა კოდს.</p><form id="otp-request"><input name="name" placeholder="სახელი და გვარი" required><input name="phone" placeholder="5XX XX XX XX" required><button class="primary">კოდის გაგზავნა</button></form></div><div id="login-step-two" hidden><h2>კოდის დადასტურება</h2><form id="otp-verify"><input name="code" inputmode="numeric" maxlength="6" placeholder="6-ნიშნა კოდი" required><button class="primary">დადასტურება</button></form><p id="debug-code"></p></div><p id="login-message" class="form-message"></p></dialog>
<script>window.ines={routes:{admission:@json(route('admissions.store')),request:@json(route('auth.request')),verify:@json(route('auth.verify'))}};</script>
<script src="{{ asset('js/app.js') }}"></script>
</body></html>
