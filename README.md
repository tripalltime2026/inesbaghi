# ინეს ბაღი — Laravel პლატფორმა

საბავშვო ბაღის საჯარო საიტის, ჩარიცხვის პროცესის, მშობელთა კაბინეტისა და ადმინისტრაციული სისტემის ერთიანი კოდი.

## მიმდინარე შესაძლებლობები

- Laravel 13 / PHP 8.3+ foundation
- PostgreSQL მონაცემთა მოდელი
- უსაფრთხო ტელეფონით OTP ავტორიზაცია და role-based redirect
- `member`, `parent`, `teacher`, `finance`, `admin` როლების server-side დაცვა
- ჩარიცხვის საჯარო ფორმა, validation და მონაცემთა ბაზაში შენახვა
- Admissions CRM: ძიება, ფილტრები, pipeline სტატუსები და პასუხისმგებელი თანამშრომელი
- follow-up და ბაღის ტურის დაგეგმვა
- განაცხადის შიდა კომენტარები და audit log
- განაცხადის transactional გარდაქმნა მშობლად, ბავშვად და pending ჩარიცხვად
- ბავშვებისა და მეურვეების ადმინისტრაციული რეესტრი
- ბავშვის პროფილი, სამედიცინო ჩანაწერი და ფოტოს თანხმობის აღრიცხვა
- ჯგუფების occupancy/capacity dashboard და roster
- capacity-დაცული pending → active ჩარიცხვის workflow
- ყოველთვიური დარიცხვების ხელით და scheduler-ით გენერაცია
- დუბლიკატებისგან დაცული `billing:generate` command
- ფასდაკლება, ჩამოწერა, გაუქმება, ნაწილობრივი და სრული გადახდები
- ცალკეული payment transaction-ები მეთოდით, რეფერენსითა და პასუხისმგებელი თანამშრომლით
- დავალიანებისა და ვადაგადაცილების ფინანსური dashboard
- ჯგუფის დღიური დასწრების roster
- ბავშვის check-in/check-out, გამყვანი პირი, არყოფნის ტიპი და შენიშვნა
- მშობლის დაცული კაბინეტი მხოლოდ საკუთარ ბავშვებზე
- მშობლის ხედში ჯგუფი, დასწრების ისტორია, დარიცხვა და დარჩენილი თანხა
- responsive საჯარო, ადმინისტრაციული, ფინანსური, მასწავლებლისა და მშობლის ინტერფეისები
- feature tests და GitHub Actions CI

## ლოკალურად გაშვება

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

შემდეგ გახსენით `http://127.0.0.1:8000`.

## ყოველთვიური დარიცხვა

ერთი თვის დარიცხვების ხელით გენერაცია:

```bash
php artisan billing:generate 2026-09 --due=2026-09-10
```

კონკრეტული ჯგუფისთვის:

```bash
php artisan billing:generate 2026-09 --due=2026-09-10 --group=1
```

`routes/console.php`-ში command დაგეგმილია ყოველი თვის პირველ რიცხვში 01:00 საათზე. Production სერვერზე Laravel scheduler-ს სჭირდება cron:

```cron
* * * * * cd /path/to/inesbaghi && php artisan schedule:run >> /dev/null 2>&1
```

## ადმინისტრატორის შექმნა

ახალი ტელეფონის ნომერი უსაფრთხოების გამო ყოველთვის `member / pending` სტატუსით იქმნება. პირველი ადმინისტრატორის როლი მონაცემთა ბაზიდან ან Tinker-ით უნდა გაიცეს:

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('phone', '+9955XXXXXXXX')->firstOrFail();
$user->update(['role' => 'admin', 'status' => 'active']);
```

`finance` როლი ხედავს მხოლოდ ფინანსურ მოდულს, `teacher` კი მხოლოდ დასწრების მოდულს.

## OTP გარემო

ლოკალურ და testing გარემოში OTP debug რეჟიმში API პასუხშიც ჩანს. Production-ში აუცილებელია:

- რეალური SMS provider-ის დაკავშირება
- `APP_DEBUG=false`
- უსაფრთხო `APP_KEY`
- HTTPS
- PostgreSQL backups
- Redis/Valkey rate limiting და queues

## შემდეგი მოდულები

1. მშობელთა სიახლეები და შეტყობინებები
2. დახურული ფოტოალბომები და consent enforcement
3. ღონისძიებები და რეგისტრაცია
4. PDF ქვითრები და ონლაინ ბანკის ინტეგრაცია
5. დასწრების შეტყობინებები მშობელთან
