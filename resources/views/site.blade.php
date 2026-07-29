<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="ინეს ბაღი — თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული საგანმანათლებლო სივრცე ბათუმში.">
<title>ინეს ბაღი — სივრცე ბედნიერი ბავშვობისთვის</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body>
<header id="header"><div class="wrap nav">
<a class="logo" href="#home"><span class="logo-mark">ი</span><span>ინეს ბაღი</span></a>
<nav class="links" id="navLinks"><a href="#about">ჩვენ შესახებ</a><a href="#programs">ასაკობრივი ჯგუფები</a><a href="#method">მიდგომა</a><a href="#club">მშობელთა კლუბი</a><a href="#faq">კითხვა–პასუხი</a><a href="#contact">კონტაქტი</a>@guest<button class="mobile-login" type="button" data-open-login>მშობლის კაბინეტი</button>@endguest</nav>
<div class="nav-actions">
<button class="btn small light" data-open>რეგისტრაცია</button>
@auth
<a class="btn small mint portal-link" href="{{ auth()->user()->hasRole('admin') ? route('admin.dashboard') : (auth()->user()->hasRole('finance') ? route('admin.payments.index') : (auth()->user()->hasRole('teacher') ? route('admin.attendance.index') : (auth()->user()->hasRole('parent') ? route('parent.dashboard') : route('home')))) }}">კაბინეტი</a>
@else
<button class="btn small mint portal-link" data-open-login>შესვლა</button>
@endauth
<button aria-label="მენიუ" class="menu-btn" id="menuBtn">☰</button>
</div>
</div></header>
<main>
<section class="hero" id="home"><div class="wrap hero-grid">
<div class="hero-copy"><span class="eyebrow">ბედნიერი ბავშვობა იწყება აქ</span><h1>სივრცე, სადაც ბავშვი იზრდება <span style="color:#729c89">სიყვარულით</span></h1><p>თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული ბაღი — უსაფრთხო გარემო, პროფესიონალი გუნდი და განვითარების გააზრებული პროგრამა.</p><div class="hero-actions"><button class="btn" data-open>ბავშვის რეგისტრაცია <span class="arrow">→</span></button><a class="btn light" href="#programs">იხილეთ პროგრამები</a></div><div class="trust"><div class="faces"><span class="face">მ</span><span class="face" style="background:var(--yellow)">ნ</span><span class="face" style="background:var(--lav)">ა</span></div><span><strong>90-მდე ბედნიერი აღსაზრდელი</strong><br>ერთიან, მზრუნველ გარემოში</span></div></div>
<div class="visual"><div class="scribble"></div><div class="arch"><div class="sun"></div><div class="tree"></div><div class="house"><span class="window w1"></span><span class="window w2"></span></div></div><div class="float-card fc1"><strong>🌿 ეკომეგობრული</strong>ჯანსაღი და უსაფრთხო სივრცე</div><div class="float-card fc2"><strong>4 ასაკობრივი ჯგუფი</strong>2-დან 6 წლამდე</div></div>
</div></section>
<section class="values"><div class="wrap value-grid"><article class="value"><div class="value-icon">♡</div><h3>მზრუნველი გარემო</h3><p>ყურადღება, ემოციური უსაფრთხოება და პატივისცემა თითოეული ბავშვის მიმართ.</p></article><article class="value"><div class="value-icon">✦</div><h3>თანამედროვე სწავლება</h3><p>თამაშზე დაფუძნებული მეთოდები და ასაკობრივ საჭიროებებზე მორგებული პროგრამა.</p></article><article class="value"><div class="value-icon">☀</div><h3>ჯანსაღი განვითარება</h3><p>დაბალანსებული კვება, მოძრაობა, სუფთა გარემო და ყოველდღიური აქტივობები.</p></article><article class="value"><div class="value-icon">◎</div><h3>მშობელთან კავშირი</h3><p>ღია კომუნიკაცია და აქტიური ჩართულობა ბავშვის განვითარების პროცესში.</p></article></div></section>
<section class="section about" id="about"><div class="wrap about-grid"><div class="about-visual"><div class="blob"><div class="blob-inner"></div><div class="plant"><i class="leaf l1"></i><i class="leaf l2"></i><i class="leaf l3"></i></div></div></div><div class="about-copy"><span class="eyebrow">ჩვენ შესახებ</span><h2>ქართული ღირებულებები და ევროპული გამოცდილება</h2><p>„ინეს ბაღი“ თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული საგანმანათლებლო სივრცეა, სადაც თითოეული აღსაზრდელის ჯანმრთელობა, უსაფრთხოება და ჰარმონიული განვითარება ჩვენი მთავარი პრიორიტეტია.</p><p>ბაღის შექმნამდე აქტიურად ვთანამშრომლობდით დასავლეთ ევროპელ კოლეგებთან. მათი გამოცდილებისა და რეკომენდაციების გათვალისწინებით შევიმუშავეთ ქართულ საგანმანათლებლო ღირებულებებზე დაფუძნებული თანამედროვე მიდგომა.</p><div class="stats"><div class="stat"><strong>4</strong><span>ასაკობრივი პროგრამა</span></div><div class="stat"><strong>12+</strong><span>პროფესიონალი პედაგოგი</span></div><div class="stat"><strong>90</strong><span>ბავშვზე გათვლილი სივრცე</span></div></div></div></div></section>
<section class="section programs" id="programs"><div class="wrap"><div class="section-head"><div><span class="eyebrow">ასაკობრივი ჯგუფები</span><h2>თითოეულ ასაკს —<br>თავისი გზა</h2></div><p>პროგრამა იცვლება ბავშვის ასაკის, ინტერესებისა და განვითარების ეტაპის მიხედვით.</p></div><div class="tabs" id="programTabs"></div><article class="program-card"><div class="program-art" id="programArt"><span class="big-age" id="bigAge"></span></div><div class="program-copy"><div class="availability"><i class="dot"></i><span id="availability"></span></div><h3 id="programTitle"></h3><p id="programDesc"></p><div class="details"><div class="detail"><span>აღმზრდელი</span><strong id="teacher"></strong></div><div class="detail"><span>დღის განრიგი</span><strong>09:00 — 18:00</strong></div><div class="detail"><span>ძირითადი მიმართულება</span><strong id="focus"></strong></div><div class="detail"><span>კვება</span><strong>4-ჯერადი, დაბალანსებული</strong></div></div><button class="btn small" data-open>ჯგუფზე რეგისტრაცია →</button></div></article></div></section>
<section class="section" id="method"><div class="wrap"><div class="section-head"><div><span class="eyebrow">ჩვენი მიდგომა</span><h2>სწავლა, რომელიც<br>ბავშვს უყვარს</h2></div><p>ყოველი დღე აერთიანებს თამაშს, აღმოჩენას, შემოქმედებასა და რეალური ცხოვრების უნარებს.</p></div><div class="method-grid"><article class="method"><span class="num">01</span><div class="method-icon">♙</div><h3>თამაშით სწავლება</h3><p>ბავშვები ბუნებრივად სწავლობენ თამაშის, კვლევის, ცდისა და გამოცდილების გზით.</p></article><article class="method"><span class="num">02</span><div class="method-icon">◌</div><h3>ინდივიდუალური მიდგომა</h3><p>ვაკვირდებით ბავშვის ტემპს, ინტერესებსა და საჭიროებებს და შესაბამისად ვგეგმავთ აქტივობებს.</p></article><article class="method"><span class="num">03</span><div class="method-icon">✎</div><h3>შემოქმედებითი განვითარება</h3><p>ხელოვნება, მუსიკა, მოძრაობა და ამბების თხრობა აძლიერებს აზროვნებასა და თვითგამოხატვას.</p></article></div></div></section>
<section class="section club" id="club"><div class="wrap club-grid"><div class="club-demo"><div class="app-head"><div class="app-user"><span class="avatar">ი</span><span>ინეს ბაღი<br><small style="font-weight:500;color:#718286">მშობელთა კლუბი</small></span></div><span>•••</span></div><div class="post"><small>დღეს · 09:30</small><h4>საზაფხულო ღონისძიების დეტალები</h4><p>პარასკევს, 17:00 საათზე, ბაღის ეზოში გაიმართება ჩვენი საზაფხულო ღონისძიება. გელოდებით ყველას!</p><div class="post-actions"><span>♡ 24</span><span>◯ 8 კომენტარი</span><span>↗ გაზიარება</span></div></div><div class="post" style="background:var(--mint2)"><small>გამოკითხვა</small><h4>რომელი დროა თქვენთვის მოსახერხებელი?</h4><div style="height:8px;margin-top:15px;border-radius:8px;background:#fff;overflow:hidden"><div style="width:68%;height:100%;background:var(--sage)"></div></div></div></div><div><span class="eyebrow">მშობელთა კლუბი</span><h2>მეტი კავშირი.<br>მეტი ჩართულობა.</h2><p class="lead">დახურული სივრცე, რომელიც აერთიანებს მშობლებსა და ბაღის გუნდს და გაძლევთ შესაძლებლობას, აქტიურად ჩაერთოთ ბაღის ცხოვრებაში.</p><div class="club-list"><div class="club-item"><span class="check">✓</span><div><strong>მნიშვნელოვანი სიახლეები</strong><span>მიიღეთ ინფორმაცია პროგრამების, აქტივობებისა და დღის ამბების შესახებ.</span></div></div><div class="club-item"><span class="check">✓</span><div><strong>ღონისძიებები და შეხვედრები</strong><span>დაგეგმეთ მონაწილეობა და მარტივად ადევნეთ თვალი კალენდარს.</span></div></div><div class="club-item"><span class="check">✓</span><div><strong>ფორუმი და გამოკითხვები</strong><span>გაუზიარეთ გამოცდილება სხვა მშობლებს და მიიღეთ მონაწილეობა გადაწყვეტილებებში.</span></div></div></div><button class="btn" data-open-login>კლუბში შესვლა →</button></div></div></section>
<section class="section faq" id="faq"><div class="wrap faq-grid"><div class="faq-intro"><span class="eyebrow">კითხვა–პასუხი</span><h2>ხშირად დასმული კითხვები</h2><p class="lead">თუ დამატებითი კითხვა გაქვთ, ჩვენი ადმინისტრაცია სიამოვნებით დაგეხმარებათ.</p><a class="btn light" href="#contact">დაგვიკავშირდით</a></div><div id="faqList"></div></div></section>
<section class="cta"><div class="wrap cta-box"><span class="eyebrow">გაიცანით ინეს ბაღი</span><h2>პირველი ნაბიჯი მშვიდი და ბედნიერი დასაწყისისკენ</h2><p>ბავშვის რეგისტრაციის ან გაცნობითი ვიზიტის დასაგეგმად შეავსეთ მოკლე ფორმა. ჩვენი ადმინისტრაცია დაგიკავშირდებათ დეტალების შესათანხმებლად.</p><button class="btn" data-open>ბავშვის რეგისტრაცია <span>→</span></button></div></section>
</main>
<footer id="contact"><div class="wrap"><div class="footer-grid"><div class="footer-brand"><a class="logo" href="#home"><span class="logo-mark" style="background:var(--mint);color:var(--ink)">ი</span><span>ინეს ბაღი</span></a><p style="margin-top:20px">თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული საგანმანათლებლო სივრცე ბათუმში.</p></div><div class="footer-col"><h4>ნავიგაცია</h4><a href="#about">ჩვენ შესახებ</a><a href="#programs">ჯგუფები</a><a href="#method">მიდგომა</a><a href="#faq">კითხვა–პასუხი</a></div><div class="footer-col"><h4>კონტაქტი</h4><a href="tel:+995555000000">+995 555 00 00 00</a><a href="mailto:hello@ines.ge">hello@ines.ge</a><span>ბათუმი, საქართველო</span></div><div class="footer-col"><h4>სამუშაო საათები</h4><span>ორშაბათი — პარასკევი</span><span>09:00 — 18:00</span><a href="#">Facebook ↗</a><a href="#">Instagram ↗</a></div></div><div class="copyright"><span>© 2026 ინეს ბაღი. ყველა უფლება დაცულია.</span><span>შექმნილია ბავშვებისა და მშობლებისთვის ♡</span></div></div></footer>
<div aria-labelledby="modalTitle" aria-modal="true" class="modal" id="modal" role="dialog"><div class="modal-box"><button aria-label="დახურვა" class="close" id="closeModal">×</button><div id="formWrap"><span class="eyebrow">რეგისტრაცია</span><h2 id="modalTitle">გადადგით პირველი ნაბიჯი</h2><p class="lead" style="font-size:14px">შეავსეთ ფორმა და ჩვენი ადმინისტრაცია დაგიკავშირდებათ დეტალების შესათანხმებლად.</p><form id="registrationForm" novalidate>
<div class="form-grid">
<div class="field"><label>მშობლის სახელი და გვარი *</label><input name="parent_name" placeholder="მაგ. ნინო ბერიძე" required></div>
<div class="field"><label>ტელეფონის ნომერი *</label><input name="phone" placeholder="+995 5XX XX XX XX" required type="tel"></div>
<div class="field"><label>ბავშვის სახელი და გვარი *</label><input name="child_name" placeholder="ბავშვის სახელი" required></div>
<div class="field"><label>დაბადების წელი</label><input max="2026" min="2018" name="birth_year" placeholder="მაგ. 2022" type="number"></div>
<div class="field"><label>ასაკობრივი ჯგუფი *</label><select name="preferred_group" required><option value="">აირჩიეთ ჯგუფი</option><option value="2-3">2–3 წელი</option><option value="3-4">3–4 წელი</option><option value="4-5">4–5 წელი</option><option value="5-6">5–6 წელი</option></select></div>
<div class="field"><label>სასწავლო წელი *</label><select name="academic_year" required><option value="2026">2026–2027</option><option value="2027">2027–2028</option></select></div>
<div class="field full"><label>სასურველი ფორმატი</label><select name="wants_tour"><option value="0">ბავშვის რეგისტრაცია</option><option value="1">გაცნობითი ვიზიტის დაგეგმვა</option></select></div>
<div class="field full"><label>სასურველი ვიზიტის თარიღი</label><input name="preferred_tour_date" type="date"></div>
<div class="field full"><label>დამატებითი ინფორმაცია</label><textarea name="comment" placeholder="მოგვწერეთ, თუ გაქვთ რაიმე მნიშვნელოვანი კითხვა ან ინფორმაცია"></textarea></div>
</div>
<p class="form-note">ფორმის გაგზავნით ეთანხმებით, რომ ადმინისტრაცია დაგიკავშირდეთ მითითებულ ნომერზე.</p>
<div aria-live="polite" class="form-status" id="registrationStatus"></div>
<button class="btn" style="width:100%" type="submit">ფორმის გაგზავნა →</button>
</form></div><div class="success" id="success"><div class="success-icon">✓</div><h2>გმადლობთ!</h2><p id="successMessage">თქვენი ინფორმაცია მიღებულია. ჩვენი ადმინისტრაცია დაგიკავშირდებათ დეტალების შესათანხმებლად.</p><button class="btn light" id="doneBtn">დახურვა</button></div></div></div>
<div aria-labelledby="loginTitle" aria-modal="true" class="modal" id="loginModal" role="dialog">
<div class="modal-box compact">
<button aria-label="დახურვა" class="close" id="closeLoginModal">×</button>
<div id="loginStepOne">
<span class="eyebrow">მშობელთა კლუბი</span>
<h2 id="loginTitle">შესვლა ტელეფონით</h2>
<p class="lead" style="font-size:14px">შეიყვანეთ სახელი და ტელეფონის ნომერი. გამოგიგზავნით 6-ნიშნა კოდს.</p>
<form class="login-grid" id="otpRequest">
<div class="field"><label>სახელი და გვარი *</label><input name="name" placeholder="სახელი და გვარი" required></div>
<div class="field"><label>ტელეფონის ნომერი *</label><input name="phone" placeholder="5XX XX XX XX" required type="tel"></div>
<button class="btn" type="submit">კოდის გაგზავნა →</button>
</form>
</div>
<div hidden id="loginStepTwo">
<span class="eyebrow">დადასტურება</span>
<h2>შეიყვანეთ SMS კოდი</h2>
<form class="login-grid" id="otpVerify">
<div class="field"><label>6-ნიშნა კოდი *</label><input inputmode="numeric" maxlength="6" name="code" placeholder="000000" required></div>
<button class="btn" type="submit">დადასტურება →</button>
</form>
<div class="login-code" hidden id="debugCode"></div>
</div>
<div aria-live="polite" class="form-status" id="loginStatus"></div>
</div>
</div>
<script>
window.ines={routes:{admission:@json(route('admissions.store')),request:@json(route('auth.request')),verify:@json(route('auth.verify'))}};
</script>
<script src="{{ asset('js/site.js') }}"></script>
</body>
</html>
