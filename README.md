# ინეს ბაღი — Laravel პლატფორმა

საბავშვო ბაღის საჯარო საიტის, ჩარიცხვის პროცესის, მშობელთა კაბინეტისა და ადმინისტრაციული სისტემის ერთიანი კოდი.

## მიმდინარე შესაძლებლობები

- Laravel 13 / PHP 8.3+ foundation
- PostgreSQL მონაცემთა მოდელი
- უსაფრთხო ტელეფონით OTP ავტორიზაცია
- `member`, `parent`, `teacher`, `finance`, `admin` როლების server-side დაცვა
- ჩარიცხვის საჯარო ფორმა, validation და მონაცემთა ბაზაში შენახვა
- Admissions CRM: ძიება, ფილტრები, pipeline სტატუსები და პასუხისმგებელი თანამშრომელი
- follow-up და ბაღის ტურის დაგეგმვა
- განაცხადის შიდა კომენტარები და audit log
- განაცხადის transactional გარდაქმნა მშობლად, ბავშვად და pending ჩარიცხვად
- ბავშვების, მეურვეების, ჯგუფების, ჩარიცხვების, გადახდებისა და community content-ის ცხრილები
- responsive საჯარო გვერდი და დაცული ადმინისტრაციული ინტერფეისი
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

## ადმინისტრატორის შექმნა

ახალი ტელეფონის ნომერი უსაფრთხოების გამო ყოველთვის `member / pending` სტატუსით იქმნება. პირველი ადმინისტრატორის როლი მონაცემთა ბაზიდან ან Tinker-ით უნდა გაიცეს:

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('phone', '+9955XXXXXXXX')->firstOrFail();
$user->update(['role' => 'admin', 'status' => 'active']);
```

## OTP გარემო

ლოკალურ და testing გარემოში OTP debug რეჟიმში API პასუხშიც ჩანს. Production-ში აუცილებელია:

- რეალური SMS provider-ის დაკავშირება
- `APP_DEBUG=false`
- უსაფრთხო `APP_KEY`
- HTTPS
- PostgreSQL backups
- Redis/Valkey rate limiting და queues

## შემდეგი მოდულები

1. ბავშვებისა და მეურვეების ადმინისტრაციული პროფილები
2. ჯგუფებში ადგილების და capacity-ის მართვა
3. დასწრების ჟურნალი
4. გადასახადები და დავალიანება
5. მშობელთა კაბინეტი და დახურული ფოტოალბომები
