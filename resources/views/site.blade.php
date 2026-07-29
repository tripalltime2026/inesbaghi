<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ინეს ბაღი — თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული კერძო საბავშვო ბაღი ბათუმში.">
    <title>ინეს ბაღი — სიყვარულით</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700;800&family=Noto+Serif+Georgian:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
</head>
<body class="final-site">
<header class="site-header" id="siteHeader">
    <button class="brand-lockup" type="button" data-page-target="home" aria-label="მთავარ გვერდზე დაბრუნება">
        <span class="brand-arch"><i></i></span>
        <span><strong>ინეს ბაღი</strong><small>კერძო საბავშვო ბაღი</small></span>
    </button>
    <nav class="site-nav" id="siteNav" aria-label="მთავარი ნავიგაცია">
        <button type="button" data-page-target="home">მთავარი</button>
        <button type="button" data-page-target="about">ჩვენ შესახებ</button>
        <button type="button" data-page-target="groups">ჯგუფები</button>
        <button type="button" data-page-target="blog">ბლოგი</button>
        <button type="button" data-page-target="faq">კითხვა-პასუხი</button>
        <button type="button" data-page-target="contact">კონტაქტი</button>
    </nav>
    <div class="site-actions">
        <button class="pill butter" type="button" data-page-target="admission">ჩარიცხვა</button>
        @auth
            @php
                $cabinetUrl = auth()->user()->hasRole('admin')
                    ? route('admin.dashboard')
                    : (auth()->user()->hasRole('finance')
                        ? route('admin.payments.index')
                        : (auth()->user()->hasRole('teacher')
                            ? route('admin.attendance.index')
                            : route('parent.dashboard')));
            @endphp
            <a class="pill navy" href="{{ $cabinetUrl }}">{{ auth()->user()->hasRole('admin') ? 'ადმინი' : 'კლუბი' }}</a>
            <span class="user-chip"><i>{{ mb_substr(auth()->user()->name, 0, 1) }}</i>{{ auth()->user()->name }}</span>
        @else
            <button class="pill navy" type="button" data-open-login>შესვლა</button>
        @endauth
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="მენიუს გახსნა">☰</button>
    </div>
</header>

<main id="publicApp">
    <section class="public-page active" data-page="home">
        <div class="hero-shell content-width">
            <div class="hero-copy">
                <span class="section-badge butter">საბავშვო ბაღი</span>
                <h1>სიყვარულით<br>ინეს ბაღი</h1>
                <p>ინდივიდუალური მიდგომა თითოეულ ბავშვთან, თანამედროვე სასწავლო პროგრამა და მზრუნველი პედაგოგები. მშობლებისთვის გამჭვირვალე კომუნიკაცია, აქტიური მონაწილეობა და განსაკუთრებული ღონისძიებები მთელი წლის განმავლობაში.</p>
                <div class="button-row">
                    @auth
                        <a class="primary-button" href="{{ $cabinetUrl ?? route('parent.dashboard') }}">შემოგვიერთდი კლუბში</a>
                    @else
                        <button class="primary-button" type="button" data-open-login>შემოგვიერთდი კლუბში</button>
                    @endauth
                    <button class="secondary-button lavender" type="button" data-page-target="admission">ჩარიცხვის განაცხადი</button>
                </div>
            </div>
            <div class="hero-art" role="img" aria-label="ინეს ბაღის ილუსტრაცია"><img src="{{ asset('images/ines-final-hero.svg') }}" alt="ინეს ბაღი — სივრცე ბავშვებისთვის"></div>
        </div>

        <section class="content-width home-offers">
            <h2>რას გთავაზობთ</h2>
            <div class="offer-grid">
                <button class="offer-card mint" type="button" data-page-target="methodology"><span>🧩</span><strong>მონტესორის მეთოდი</strong><small>დამოუკიდებლობა და თამაშზე დაფუძნებული სწავლება</small></button>
                <button class="offer-card lavender" type="button" data-page-target="groups"><span>👶</span><strong>4 ასაკობრივი ჯგუფი</strong><small>ასაკზე მორგებული სასწავლო პროგრამები</small></button>
                <button class="offer-card butter" type="button" data-page-target="team"><span>🌟</span><strong>გამოცდილი გუნდი</strong><small>პედაგოგები და ფსიქოლოგი, რომლებიც ბავშვს ინდივიდუალურად უდგებიან</small></button>
                <button class="offer-card peach" type="button" data-open-login><span>💬</span><strong>მშობელთა კლუბი</strong><small>სიახლეები, ღონისძიებები, ფორუმი და გამოკითხვები</small></button>
            </div>
        </section>

        <section class="latest-band">
            <div class="content-width latest-grid">
                <div><span class="section-badge mint">ბოლო სიახლეები</span><h2>ბლოგი მშობლებისთვის</h2><p>რჩევები აღზრდაზე, კვებაზე, დღის რეჟიმზე და სკოლისთვის მზადებაზე.</p><button class="ghost-button" type="button" data-page-target="blog">ყველა სტატია →</button></div>
                <div class="mini-post-grid">
                    <article><i class="mint"></i><small>8 ივლისი, 2026</small><strong>როგორ ვამზადოთ ბავშვი ბაღისთვის — 5 რჩევა</strong></article>
                    <article><i class="butter"></i><small>2 ივლისი, 2026</small><strong>ჯანსაღი კვება პატარებისთვის</strong></article>
                    <article><i class="lavender"></i><small>25 ივნისი, 2026</small><strong>თამაშის მნიშვნელობა 3-4 წლის ასაკში</strong></article>
                </div>
            </div>
        </section>
    </section>

    <section class="public-page" data-page="about">
        <div class="page-intro content-width"><span class="section-badge mint">ჩვენ შესახებ</span><h1>სივრცე, სადაც ბავშვი იზრდება სიყვარულით</h1></div>
        <div class="content-width prose-layout">
            <article class="prose-card">
                <p>„ინეს ბაღი“ თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული საგანმანათლებლო სივრცეა, სადაც თითოეული აღსაზრდელის ჯანმრთელობა, უსაფრთხოება და ჰარმონიული განვითარება ჩვენი მთავარი პრიორიტეტია.</p>
                <p>ბაღის შექმნამდე აქტიურად ვთანამშრომლობდით დასავლეთ ევროპელ კოლეგებთან. მათი გამოცდილებისა და რეკომენდაციების გათვალისწინებით, ქართულ საგანმანათლებლო ღირებულებებზე დაფუძნებული სწავლების თანამედროვე მიდგომა შევიმუშავეთ.</p>
                <p>ჩვენი პროგრამა ითვალისწინებს ბავშვის ასაკს, ინტერესებსა და ინდივიდუალურ საჭიროებებს. სწავლა მიმდინარეობს თამაშით, შემოქმედებითი აქტივობებითა და ინოვაციური მეთოდებით, რაც ბავშვებს ეხმარება დამოუკიდებელი აზროვნების, კომუნიკაციისა და სოციალური უნარების განვითარებაში.</p>
            </article>
            <div class="story-stack">
                <article class="story-card mint"><span>ჩვენი ისტორია</span><strong>დაარსდა 2022 წელს ცოტა ბავშვით და დიდი ოცნებით. დღეს ეს არის თბილი სივრცე ორმოცამდე პატარისთვის.</strong></article>
                <article class="story-card butter"><span>ჩვენი ფილოსოფია</span><strong>ბავშვი არის აქტიური აღმომჩენი — ჩვენი როლი მისი ცნობისმოყვარეობის მხარდაჭერაა.</strong></article>
                <article class="story-card lavender"><span>ჩვენი ღირებულებები</span><strong>პატივისცემა · უსაფრთხოება · ინდივიდუალური მიდგომა · გამჭვირვალე კომუნიკაცია მშობლებთან · სიხარული ყოველდღიურობაში</strong></article>
            </div>
        </div>
    </section>

    <section class="public-page" data-page="methodology">
        <div class="page-intro content-width"><span class="section-badge lavender">მეთოდოლოგია</span><h1>ბავშვის ბუნებრივ რიტმზე მორგებული სწავლება</h1><p>ჩვენ ვიყენებთ მონტესორის ელემენტებს, გამდიდრებულს თამაშზე დაფუძნებული სწავლებით. თითოეული ჯგუფის დღის რიტმი ბავშვის ბუნებრივ ციკლს მიჰყვება.</p></div>
        <div class="content-width method-grid">
            <article class="method-card mint"><span>🧩</span><h3>სწავლების მეთოდი</h3><p>ბავშვი თავად ირჩევს აქტივობას მოწოდებული მასალებიდან.</p></article>
            <article class="method-card butter"><span>🎨</span><h3>თამაშზე დაფუძნებული სწავლება</h3><p>შემოქმედებითი პროცესები ემოციური და სოციალური განვითარებისთვის.</p></article>
            <article class="method-card lavender"><span>🌱</span><h3>ბუნებრივი დღის რიტმი</h3><p>ძილი, კვება და თამაში ბავშვის ბიოლოგიურ ციკლს მიჰყვება.</p></article>
        </div>
    </section>

    <section class="public-page" data-page="groups">
        <div class="page-intro content-width"><span class="section-badge butter">ჯგუფები</span><h1>4 ასაკობრივი ჯგუფი</h1><p>თითოეულ ჯგუფს საკუთარი პროგრამა და დღის რიტმი აქვს.</p></div>
        <div class="content-width group-tabs" id="groupTabs"></div>
        <div class="content-width group-detail">
            <article class="group-summary" id="groupSummary"></article>
            <article class="schedule-card"><h2>დღის განრიგი</h2><div id="groupSchedule"></div></article>
        </div>
    </section>

    <section class="public-page" data-page="team">
        <div class="page-intro content-width"><span class="section-badge peach">გუნდი</span><h1>გამოცდილი პედაგოგები</h1><p>გუნდი, რომელიც ზრუნავს თითოეულ ბავშვზე ინდივიდუალურად.</p></div>
        <div class="content-width team-grid" id="teamGrid"></div>
    </section>

    <section class="public-page" data-page="gallery">
        <div class="page-intro content-width"><span class="section-badge mint">გალერეა</span><h1>ბოლო ფოტოები ჩვენი ბაღიდან</h1><p>გალერეა ხელმისაწვდომია მხოლოდ კლუბის წევრებისთვის.</p></div>
        @guest
            <div class="content-width locked-card"><span>🔒</span><h2>გალერეა ხელმისაწვდომია მხოლოდ კლუბის წევრებისთვის</h2><p>გაიარეთ მარტივი ვერიფიკაცია ტელეფონის ნომრით — ბაღის ფოტოები, ღონისძიებები და სიახლეები დაუყოვნებლივ გაიხსნება.</p><button class="primary-button" type="button" data-open-login>შესვლა / რეგისტრაცია</button></div>
        @else
            <div class="content-width gallery-grid" id="galleryGrid"></div>
        @endguest
    </section>

    <section class="public-page" data-page="blog">
        <div class="page-intro content-width"><span class="section-badge lavender">ბლოგი</span><h1>სტატიები მშობლებისთვის</h1><p>აღზრდაზე, კვებაზე, დღის რეჟიმზე და ბავშვის განვითარებაზე.</p></div>
        <div class="content-width blog-grid" id="blogGrid"></div>
    </section>

    <section class="public-page" data-page="faq">
        <div class="page-intro content-width"><span class="section-badge butter">კითხვა-პასუხი</span><h1>ხშირად დასმული კითხვები</h1><p>ფასების დეტალები გეგზავნებათ ვერიფიკაციის შემდეგ, მშობელთა კლუბის პროფილში.</p></div>
        <div class="content-width faq-list" id="faqList"></div>
    </section>

    <section class="public-page" data-page="contact">
        <div class="page-intro content-width"><span class="section-badge mint">კონტაქტი</span><h1>დაგვიკავშირდით</h1><p>ნებისმიერ საკითხზე — სიამოვნებით დაგეხმარებით.</p></div>
        <div class="content-width contact-grid">
            <article><span>მისამართი</span><strong>ლერმონტოვის 53, ქ. ბათუმი</strong></article>
            <article><span>ცხელი ხაზი</span><a href="tel:+995555411831">+995 555 41 18 31</a></article>
            <article><span>სამუშაო საათები</span><strong>ორშ–პარ, 08:00–19:00</strong></article>
            <article class="map-card"><span>📍</span><strong>რუკა</strong><small>ლერმონტოვის 53, ბათუმი</small></article>
        </div>
    </section>

    <section class="public-page" data-page="admission">
        <div class="page-intro content-width"><span class="section-badge peach">მიღებაზე რეგისტრაცია</span><h1>შეავსეთ ჩარიცხვის განაცხადი ან დაგეგმეთ გაცნობითი ვიზიტი</h1></div>
        <div class="content-width admission-layout">
            <form class="final-form" id="registrationForm" novalidate>
                <div class="form-grid">
                    <label><span>მშობლის სახელი და გვარი *</span><input name="parent_name" required placeholder="მაგ. ნინო ბერიძე"></label>
                    <label><span>ტელეფონის ნომერი *</span><input name="phone" required type="tel" placeholder="+995 5XX XX XX XX"></label>
                    <label><span>ბავშვის სახელი და გვარი *</span><input name="child_name" required placeholder="ბავშვის სახელი"></label>
                    <label><span>დაბადების წელი</span><input name="birth_year" type="number" min="2018" max="2026" placeholder="მაგ. 2022"></label>
                </div>
                <fieldset><legend>სასურველი ჯგუფი</legend><div class="choice-row" id="admissionGroups"></div></fieldset>
                <fieldset><legend>სასწავლო წელი</legend><div class="choice-row"><label class="choice active"><input type="radio" name="academic_year" value="2026" checked>2026–2027</label><label class="choice"><input type="radio" name="academic_year" value="2027">2027–2028</label></div><small>შესაძლებელია ჩარიცხვის მოთხოვნის გაგზავნა წლით ადრეც — თქვენი ადგილი დაცული იქნება.</small></fieldset>
                <label class="switch-row"><input type="checkbox" name="wants_tour" value="1" checked><span>გვსურს დავჯავშნოთ ვიზიტი ბაღში</span></label>
                <label><span>სასურველი ვიზიტის თარიღი</span><input name="preferred_tour_date" type="date"></label>
                <label><span>დამატებითი ინფორმაცია</span><textarea name="comment" placeholder="მოგვწერეთ, თუ გაქვთ რაიმე მნიშვნელოვანი კითხვა ან ინფორმაცია"></textarea></label>
                <p class="form-note">ფორმის გაგზავნით ეთანხმებით, რომ ადმინისტრაცია დაგიკავშირდეთ მითითებულ ნომერზე.</p>
                <div class="form-status" id="registrationStatus" aria-live="polite"></div>
                <button class="primary-button full" type="submit">განაცხადის გაგზავნა</button>
            </form>
            <aside class="admission-note butter"><span>🌱</span><h2>გმადლობთ ინტერესისთვის</h2><p>განაცხადის მიღების შემდეგ ადმინისტრაცია დაგიკავშირდებათ, გაგაცნობთ პირობებს და ვიზიტის დროს შეგითანხმებთ.</p><small>თქვენი განაცხადის სტატუსს შემდგომში კლუბის პროფილიდანაც ნახავთ.</small></aside>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="content-width footer-row"><span>© 2026 ინეს ბაღი · ლერმონტოვის 53, ბათუმი</span><a href="tel:+995555411831">+995 555 41 18 31</a></div>
</footer>

<div class="modal" id="loginModal" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
    <div class="modal-card compact">
        <button class="modal-close" type="button" data-close-login aria-label="დახურვა">×</button>
        <div id="loginStepOne">
            <span class="section-badge mint">შესვლა / რეგისტრაცია</span>
            <h2 id="loginTitle">შესვლა ტელეფონით</h2>
            <p id="loginLead">შეიყვანეთ სახელი და ტელეფონის ნომერი.</p>
            <form class="final-form" id="otpRequest">
                <label><span>სახელი და გვარი *</span><input name="name" required placeholder="სახელი და გვარი"></label>
                <label><span>ტელეფონის ნომერი *</span><input name="phone" required type="tel" placeholder="5XX XX XX XX"></label>
                <div class="demo-auth-note" id="demoAuthNote" hidden></div>
                <button class="primary-button full" id="loginSubmit" type="submit">გაგრძელება</button>
            </form>
        </div>
        <div id="loginStepTwo" hidden>
            <span class="section-badge butter">დადასტურება</span>
            <h2>შეიყვანეთ SMS კოდი</h2>
            <p>კოდი გამოგზავნილია ნომერზე <strong id="otpPhone"></strong></p>
            <form class="final-form" id="otpVerify"><label><span>6-ნიშნა კოდი *</span><input name="code" required inputmode="numeric" maxlength="6" placeholder="000000"></label><div class="login-code" id="debugCode" hidden></div><button class="primary-button full" type="submit">დადასტურება</button></form>
        </div>
        <div class="form-status" id="loginStatus" aria-live="polite"></div>
    </div>
</div>

<script>
window.ines = {
    routes: {
        admission: @json(route('admissions.store')),
        request: @json(route('auth.request')),
        verify: @json(route('auth.verify')),
        demoStatus: @json(route('auth.demo.status')),
        demoLogin: @json(route('auth.demo.login'))
    }
};
</script>
<script src="{{ asset('js/site.js') }}"></script>
</body>
</html>
