# ინეს ბაღი — Laravel პლატფორმა

საბავშვო ბაღის საჯარო საიტის, ჩარიცხვის პროცესის, მშობელთა კაბინეტისა და ადმინისტრაციული სისტემის ერთიანი კოდი.

## პირველი სამუშაო ინკრემენტი

- Laravel 13 / PHP 8.3+ foundation
- PostgreSQL მონაცემთა მოდელი
- უსაფრთხო ტელეფონით OTP ავტორიზაცია
- ჩარიცხვის რეალური API და server-side validation
- `member`, `parent`, `teacher`, `finance`, `admin` როლების დაცვა
- ბავშვების, მეურვეების, ჯგუფების, ჩარიცხვების, გადახდებისა და community content-ის ცხრილები
- responsive public page და დაცული admin entry point

## გაშვება

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

OTP ლოკალურ გარემოში log-ში იწერება და debug რეჟიმში UI-შიც ჩანს. Production-ში უნდა ჩაირთოს რეალური SMS provider და `APP_DEBUG=false`.
