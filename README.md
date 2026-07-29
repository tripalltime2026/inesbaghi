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
- მშობლის დაცული კაბინეტი მხოლოდ საკუთარ ბავშვებზე
- ჩარიცხვისა და დარიცხვების მშობლის ხედები
- ბავშვების, მეურვეების, ჯგუფების, ჩარიცხვების, გადახდებისა და community content-ის ცხრილები
- responsive საჯარო, ადმინისტრაციული და მშობლის ინტერფეისები
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

1. ყოველთვიური დარიცხვების ავტომატური გენერაცია და გადახდის აღრიცხვა
2. დასწრების ჟურნალი და ბავშვის მოსვლა/წასვლა
3. მშობელთა სიახლეები და შეტყობინებები
4. დახურული ფოტოალბომები და consent enforcement
5. ღონისძიებები და რეგისტრაცია
