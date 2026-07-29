<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\SiteContentEntry;
use App\Models\SiteItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ManagedContent
{
    public function sectionLabels(): array
    {
        return [
            'identity' => 'ბრენდი და მთავარი ეკრანი',
            'home' => 'მთავარი გვერდი',
            'about' => 'ჩვენ შესახებ',
            'methodology' => 'მეთოდოლოგია',
            'catalog' => 'ჯგუფები, გუნდი და ბლოგი',
            'contact' => 'კონტაქტი',
            'admission' => 'ჩარიცხვის ფორმა',
            'auth' => 'შესვლა და რეგისტრაცია',
            'footer' => 'Footer',
        ];
    }

    public function itemTypeLabels(): array
    {
        return [
            'group' => 'ასაკობრივი ჯგუფები',
            'team' => 'გუნდი',
            'faq' => 'კითხვა-პასუხი',
            'gallery' => 'გალერეა',
        ];
    }

    public function textDefinitions(): array
    {
        return [
            $this->definition('identity.brand_name', 'identity', 'ბაღის დასახელება', 'ინეს ბაღი', 'ინეს ბაღი'),
            $this->definition('identity.brand_subtitle', 'identity', 'ბრენდის ქვესათაური', 'კერძო საბავშვო ბაღი', 'კერძო საბავშვო ბაღი'),
            $this->definition('identity.hero_badge', 'identity', 'Hero badge', 'საბავშვო ბაღი', 'საბავშვო ბაღი'),
            $this->definition('identity.hero_title', 'identity', 'Hero მთავარი სათაური', "სიყვარულით\nინეს ბაღი", 'სიყვარულით<br>ინეს ბაღი', 'linebreak'),
            $this->definition('identity.hero_text', 'identity', 'Hero აღწერა', 'ინდივიდუალური მიდგომა თითოეულ ბავშვთან, თანამედროვე სასწავლო პროგრამა და მზრუნველი პედაგოგები. მშობლებისთვის გამჭვირვალე კომუნიკაცია, აქტიური მონაწილეობა და განსაკუთრებული ღონისძიებები მთელი წლის განმავლობაში.', 'ინდივიდუალური მიდგომა თითოეულ ბავშვთან, თანამედროვე სასწავლო პროგრამა და მზრუნველი პედაგოგები. მშობლებისთვის გამჭვირვალე კომუნიკაცია, აქტიური მონაწილეობა და განსაკუთრებული ღონისძიებები მთელი წლის განმავლობაში.', 'textarea'),
            $this->definition('identity.primary_cta', 'identity', 'მთავარი CTA', 'შემოგვიერთდი კლუბში', 'შემოგვიერთდი კლუბში'),
            $this->definition('identity.secondary_cta', 'identity', 'მეორე CTA', 'ჩარიცხვის განაცხადი', 'ჩარიცხვის განაცხადი'),

            $this->definition('home.offers_title', 'home', 'შეთავაზებების სათაური', 'რას გთავაზობთ', 'რას გთავაზობთ'),
            $this->definition('home.offer_method_title', 'home', 'მეთოდის ბარათის სათაური', 'მონტესორის მეთოდი', 'მონტესორის მეთოდი'),
            $this->definition('home.offer_method_text', 'home', 'მეთოდის ბარათის ტექსტი', 'დამოუკიდებლობა და თამაშზე დაფუძნებული სწავლება', 'დამოუკიდებლობა და თამაშზე დაფუძნებული სწავლება'),
            $this->definition('home.offer_groups_title', 'home', 'ჯგუფების ბარათის სათაური', '4 ასაკობრივი ჯგუფი', '4 ასაკობრივი ჯგუფი'),
            $this->definition('home.offer_groups_text', 'home', 'ჯგუფების ბარათის ტექსტი', 'ასაკზე მორგებული სასწავლო პროგრამები', 'ასაკზე მორგებული სასწავლო პროგრამები'),
            $this->definition('home.offer_team_title', 'home', 'გუნდის ბარათის სათაური', 'გამოცდილი გუნდი', 'გამოცდილი გუნდი'),
            $this->definition('home.offer_team_text', 'home', 'გუნდის ბარათის ტექსტი', 'პედაგოგები და ფსიქოლოგი, რომლებიც ბავშვს ინდივიდუალურად უდგებიან', 'პედაგოგები და ფსიქოლოგი, რომლებიც ბავშვს ინდივიდუალურად უდგებიან'),
            $this->definition('home.offer_club_title', 'home', 'კლუბის ბარათის სათაური', 'მშობელთა კლუბი', 'მშობელთა კლუბი'),
            $this->definition('home.offer_club_text', 'home', 'კლუბის ბარათის ტექსტი', 'სიახლეები, ღონისძიებები, ფორუმი და გამოკითხვები', 'სიახლეები, ღონისძიებები, ფორუმი და გამოკითხვები'),
            $this->definition('home.blog_badge', 'home', 'ბლოგის badge', 'ბოლო სიახლეები', 'ბოლო სიახლეები'),
            $this->definition('home.blog_title', 'home', 'ბლოგის მთავარი სათაური', 'ბლოგი მშობლებისთვის', 'ბლოგი მშობლებისთვის'),
            $this->definition('home.blog_text', 'home', 'ბლოგის აღწერა', 'რჩევები აღზრდაზე, კვებაზე, დღის რეჟიმზე და სკოლისთვის მზადებაზე.', 'რჩევები აღზრდაზე, კვებაზე, დღის რეჟიმზე და სკოლისთვის მზადებაზე.'),

            $this->definition('about.title', 'about', 'გვერდის სათაური', 'სივრცე, სადაც ბავშვი იზრდება სიყვარულით', 'სივრცე, სადაც ბავშვი იზრდება სიყვარულით'),
            $this->definition('about.paragraph_1', 'about', 'პირველი აბზაცი', '„ინეს ბაღი“ თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული საგანმანათლებლო სივრცეა, სადაც თითოეული აღსაზრდელის ჯანმრთელობა, უსაფრთხოება და ჰარმონიული განვითარება ჩვენი მთავარი პრიორიტეტია.', '„ინეს ბაღი“ თანამედროვე, ეკომეგობრული და ბავშვზე ორიენტირებული საგანმანათლებლო სივრცეა, სადაც თითოეული აღსაზრდელის ჯანმრთელობა, უსაფრთხოება და ჰარმონიული განვითარება ჩვენი მთავარი პრიორიტეტია.', 'textarea'),
            $this->definition('about.paragraph_2', 'about', 'მეორე აბზაცი', 'ბაღის შექმნამდე აქტიურად ვთანამშრომლობდით დასავლეთ ევროპელ კოლეგებთან. მათი გამოცდილებისა და რეკომენდაციების გათვალისწინებით, ქართულ საგანმანათლებლო ღირებულებებზე დაფუძნებული სწავლების თანამედროვე მიდგომა შევიმუშავეთ.', 'ბაღის შექმნამდე აქტიურად ვთანამშრომლობდით დასავლეთ ევროპელ კოლეგებთან. მათი გამოცდილებისა და რეკომენდაციების გათვალისწინებით, ქართულ საგანმანათლებლო ღირებულებებზე დაფუძნებული სწავლების თანამედროვე მიდგომა შევიმუშავეთ.', 'textarea'),
            $this->definition('about.paragraph_3', 'about', 'მესამე აბზაცი', 'ჩვენი პროგრამა ითვალისწინებს ბავშვის ასაკს, ინტერესებსა და ინდივიდუალურ საჭიროებებს. სწავლა მიმდინარეობს თამაშით, შემოქმედებითი აქტივობებითა და ინოვაციური მეთოდებით, რაც ბავშვებს ეხმარება დამოუკიდებელი აზროვნების, კომუნიკაციისა და სოციალური უნარების განვითარებაში.', 'ჩვენი პროგრამა ითვალისწინებს ბავშვის ასაკს, ინტერესებსა და ინდივიდუალურ საჭიროებებს. სწავლა მიმდინარეობს თამაშით, შემოქმედებითი აქტივობებითა და ინოვაციური მეთოდებით, რაც ბავშვებს ეხმარება დამოუკიდებელი აზროვნების, კომუნიკაციისა და სოციალური უნარების განვითარებაში.', 'textarea'),
            $this->definition('about.story', 'about', 'ჩვენი ისტორია', 'დაარსდა 2022 წელს ცოტა ბავშვით და დიდი ოცნებით. დღეს ეს არის თბილი სივრცე ორმოცამდე პატარისთვის.', 'დაარსდა 2022 წელს ცოტა ბავშვით და დიდი ოცნებით. დღეს ეს არის თბილი სივრცე ორმოცამდე პატარისთვის.', 'textarea'),
            $this->definition('about.philosophy', 'about', 'ჩვენი ფილოსოფია', 'ბავშვი არის აქტიური აღმომჩენი — ჩვენი როლი მისი ცნობისმოყვარეობის მხარდაჭერაა.', 'ბავშვი არის აქტიური აღმომჩენი — ჩვენი როლი მისი ცნობისმოყვარეობის მხარდაჭერაა.', 'textarea'),
            $this->definition('about.values', 'about', 'ჩვენი ღირებულებები', 'პატივისცემა · უსაფრთხოება · ინდივიდუალური მიდგომა · გამჭვირვალე კომუნიკაცია მშობლებთან · სიხარული ყოველდღიურობაში', 'პატივისცემა · უსაფრთხოება · ინდივიდუალური მიდგომა · გამჭვირვალე კომუნიკაცია მშობლებთან · სიხარული ყოველდღიურობაში', 'textarea'),

            $this->definition('methodology.title', 'methodology', 'მეთოდოლოგიის სათაური', 'ბავშვის ბუნებრივ რიტმზე მორგებული სწავლება', 'ბავშვის ბუნებრივ რიტმზე მორგებული სწავლება'),
            $this->definition('methodology.intro', 'methodology', 'მეთოდოლოგიის აღწერა', 'ჩვენ ვიყენებთ მონტესორის ელემენტებს, გამდიდრებულს თამაშზე დაფუძნებული სწავლებით. თითოეული ჯგუფის დღის რიტმი ბავშვის ბუნებრივ ციკლს მიჰყვება.', 'ჩვენ ვიყენებთ მონტესორის ელემენტებს, გამდიდრებულს თამაშზე დაფუძნებული სწავლებით. თითოეული ჯგუფის დღის რიტმი ბავშვის ბუნებრივ ციკლს მიჰყვება.', 'textarea'),
            $this->definition('methodology.card_1_text', 'methodology', 'პირველი ბარათის ტექსტი', 'ბავშვი თავად ირჩევს აქტივობას მოწოდებული მასალებიდან.', 'ბავშვი თავად ირჩევს აქტივობას მოწოდებული მასალებიდან.'),
            $this->definition('methodology.card_2_text', 'methodology', 'მეორე ბარათის ტექსტი', 'შემოქმედებითი პროცესები ემოციური და სოციალური განვითარებისთვის.', 'შემოქმედებითი პროცესები ემოციური და სოციალური განვითარებისთვის.'),
            $this->definition('methodology.card_3_text', 'methodology', 'მესამე ბარათის ტექსტი', 'ძილი, კვება და თამაში ბავშვის ბიოლოგიურ ციკლს მიჰყვება.', 'ძილი, კვება და თამაში ბავშვის ბიოლოგიურ ციკლს მიჰყვება.'),

            $this->definition('catalog.groups_intro', 'catalog', 'ჯგუფების გვერდის აღწერა', 'თითოეულ ჯგუფს საკუთარი პროგრამა და დღის რიტმი აქვს.', 'თითოეულ ჯგუფს საკუთარი პროგრამა და დღის რიტმი აქვს.'),
            $this->definition('catalog.team_title', 'catalog', 'გუნდის გვერდის სათაური', 'გამოცდილი პედაგოგები', 'გამოცდილი პედაგოგები'),
            $this->definition('catalog.team_intro', 'catalog', 'გუნდის გვერდის აღწერა', 'გუნდი, რომელიც ზრუნავს თითოეულ ბავშვზე ინდივიდუალურად.', 'გუნდი, რომელიც ზრუნავს თითოეულ ბავშვზე ინდივიდუალურად.'),
            $this->definition('catalog.gallery_title', 'catalog', 'გალერეის სათაური', 'ბოლო ფოტოები ჩვენი ბაღიდან', 'ბოლო ფოტოები ჩვენი ბაღიდან'),
            $this->definition('catalog.gallery_intro', 'catalog', 'გალერეის აღწერა', 'გალერეა ხელმისაწვდომია მხოლოდ კლუბის წევრებისთვის.', 'გალერეა ხელმისაწვდომია მხოლოდ კლუბის წევრებისთვის.'),
            $this->definition('catalog.blog_title', 'catalog', 'ბლოგის გვერდის სათაური', 'სტატიები მშობლებისთვის', 'სტატიები მშობლებისთვის'),
            $this->definition('catalog.blog_intro', 'catalog', 'ბლოგის გვერდის აღწერა', 'აღზრდაზე, კვებაზე, დღის რეჟიმზე და ბავშვის განვითარებაზე.', 'აღზრდაზე, კვებაზე, დღის რეჟიმზე და ბავშვის განვითარებაზე.'),
            $this->definition('catalog.faq_title', 'catalog', 'FAQ სათაური', 'ხშირად დასმული კითხვები', 'ხშირად დასმული კითხვები'),
            $this->definition('catalog.faq_intro', 'catalog', 'FAQ აღწერა', 'ფასების დეტალები გეგზავნებათ ვერიფიკაციის შემდეგ, მშობელთა კლუბის პროფილში.', 'ფასების დეტალები გეგზავნებათ ვერიფიკაციის შემდეგ, მშობელთა კლუბის პროფილში.'),

            $this->definition('contact.title', 'contact', 'კონტაქტის სათაური', 'დაგვიკავშირდით', 'დაგვიკავშირდით'),
            $this->definition('contact.intro', 'contact', 'კონტაქტის აღწერა', 'ნებისმიერ საკითხზე — სიამოვნებით დაგეხმარებით.', 'ნებისმიერ საკითხზე — სიამოვნებით დაგეხმარებით.'),
            $this->definition('contact.address', 'contact', 'მისამართი', 'ლერმონტოვის 53, ქ. ბათუმი', 'ლერმონტოვის 53, ქ. ბათუმი'),
            $this->definition('contact.map_address', 'contact', 'რუკის მისამართი', 'ლერმონტოვის 53, ბათუმი', 'ლერმონტოვის 53, ბათუმი'),
            $this->definition('contact.phone_display', 'contact', 'ტელეფონი ეკრანზე', '+995 555 41 18 31', '+995 555 41 18 31'),
            $this->definition('contact.phone_href', 'contact', 'ტელეფონი ბმულისთვის', '+995555411831', '+995555411831'),
            $this->definition('contact.hours', 'contact', 'სამუშაო საათები', 'ორშ–პარ, 08:00–19:00', 'ორშ–პარ, 08:00–19:00'),

            $this->definition('admission.title', 'admission', 'ჩარიცხვის გვერდის სათაური', 'შეავსეთ ჩარიცხვის განაცხადი ან დაგეგმეთ გაცნობითი ვიზიტი', 'შეავსეთ ჩარიცხვის განაცხადი ან დაგეგმეთ გაცნობითი ვიზიტი', 'textarea'),
            $this->definition('admission.note_title', 'admission', 'მადლობის ბლოკის სათაური', 'გმადლობთ ინტერესისთვის', 'გმადლობთ ინტერესისთვის'),
            $this->definition('admission.note_text', 'admission', 'მადლობის ბლოკის ტექსტი', 'განაცხადის მიღების შემდეგ ადმინისტრაცია დაგიკავშირდებათ, გაგაცნობთ პირობებს და ვიზიტის დროს შეგითანხმებთ.', 'განაცხადის მიღების შემდეგ ადმინისტრაცია დაგიკავშირდებათ, გაგაცნობთ პირობებს და ვიზიტის დროს შეგითანხმებთ.', 'textarea'),
            $this->definition('admission.submit', 'admission', 'გაგზავნის ღილაკი', 'განაცხადის გაგზავნა', 'განაცხადის გაგზავნა'),
            $this->definition('admission.privacy', 'admission', 'ფორმის თანხმობის ტექსტი', 'ფორმის გაგზავნით ეთანხმებით, რომ ადმინისტრაცია დაგიკავშირდეთ მითითებულ ნომერზე.', 'ფორმის გაგზავნით ეთანხმებით, რომ ადმინისტრაცია დაგიკავშირდეთ მითითებულ ნომერზე.', 'textarea'),

            $this->definition('auth.title', 'auth', 'შესვლის სათაური', 'შესვლა ტელეფონით', 'შესვლა ტელეფონით'),
            $this->definition('auth.lead', 'auth', 'შესვლის აღწერა', 'შეიყვანეთ სახელი და ტელეფონის ნომერი.', 'შეიყვანეთ სახელი და ტელეფონის ნომერი.'),
            $this->definition('auth.continue', 'auth', 'გაგრძელების ღილაკი', 'გაგრძელება', 'გაგრძელება'),

            $this->definition('footer.copyright', 'footer', 'Footer ტექსტი', '© 2026 ინეს ბაღი · ლერმონტოვის 53, ბათუმი', '© 2026 ინეს ბაღი · ლერმონტოვის 53, ბათუმი'),
        ];
    }

    public function ensureDefaults(): void
    {
        if (! Schema::hasTable('site_content_entries')) {
            return;
        }

        DB::transaction(function (): void {
            foreach ($this->textDefinitions() as $index => $definition) {
                SiteContentEntry::firstOrCreate(
                    ['key' => $definition['key']],
                    [
                        'section' => $definition['section'],
                        'label' => $definition['label'],
                        'value' => $definition['default'],
                        'input_type' => $definition['type'],
                        'sort_order' => $index,
                    ],
                );
            }

            if (Schema::hasTable('site_items')) {
                foreach ($this->defaultItems() as $type => $items) {
                    if (SiteItem::where('type', $type)->exists()) {
                        continue;
                    }

                    foreach ($items as $index => $item) {
                        SiteItem::create(array_merge($item, [
                            'type' => $type,
                            'sort_order' => $index,
                            'is_active' => true,
                        ]));
                    }
                }
            }

            if (Schema::hasTable('blog_posts') && ! BlogPost::exists()) {
                foreach ($this->defaultPosts() as $index => $post) {
                    BlogPost::create(array_merge($post, ['sort_order' => $index]));
                }
            }
        });
    }

    public function textValues(): array
    {
        $stored = Schema::hasTable('site_content_entries')
            ? SiteContentEntry::query()->pluck('value', 'key')->all()
            : [];

        $values = [];
        foreach ($this->textDefinitions() as $definition) {
            $values[$definition['key']] = array_key_exists($definition['key'], $stored)
                ? (string) $stored[$definition['key']]
                : $definition['default'];
        }

        return $values;
    }

    public function groupedTextEntries(): array
    {
        $this->ensureDefaults();
        $values = $this->textValues();
        $grouped = [];

        foreach ($this->textDefinitions() as $definition) {
            $definition['value'] = $values[$definition['key']];
            $grouped[$definition['section']][] = $definition;
        }

        return $grouped;
    }

    public function saveTextValues(array $values, ?int $userId): void
    {
        $definitions = collect($this->textDefinitions())->keyBy('key');

        DB::transaction(function () use ($values, $userId, $definitions): void {
            foreach ($values as $key => $value) {
                if (! $definitions->has($key)) {
                    continue;
                }

                $definition = $definitions->get($key);
                SiteContentEntry::updateOrCreate(
                    ['key' => $key],
                    [
                        'section' => $definition['section'],
                        'label' => $definition['label'],
                        'value' => (string) $value,
                        'input_type' => $definition['type'],
                        'sort_order' => $definition['sort_order'] ?? 0,
                        'updated_by' => $userId,
                    ],
                );
            }
        });
    }

    public function applyTextToHtml(string $html): string
    {
        $this->ensureDefaults();
        $values = $this->textValues();
        $definitions = $this->textDefinitions();

        usort($definitions, fn (array $a, array $b): int => mb_strlen($b['match']) <=> mb_strlen($a['match']));

        foreach ($definitions as $definition) {
            $value = $values[$definition['key']] ?? $definition['default'];
            $replacement = $definition['type'] === 'linebreak'
                ? nl2br(e($value), false)
                : e($value);

            $html = str_replace($definition['match'], $replacement, $html);
        }

        return $html;
    }

    public function publicPayload(): array
    {
        $this->ensureDefaults();

        $items = collect($this->defaultItems());
        if (Schema::hasTable('site_items')) {
            $items = SiteItem::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('type');
        }

        $payload = [];
        foreach (SiteItem::TYPES as $type) {
            $payload[$type] = collect($items->get($type, []))->map(function ($item) {
                if ($item instanceof SiteItem) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'subtitle' => $item->subtitle,
                        'body' => $item->body,
                        'badge' => $item->badge,
                        'color' => $item->color ?: '#A9D3C9',
                        'meta' => $item->meta ?: [],
                        'image_url' => $item->image ? route('content.item-image', $item) : null,
                        'image_alt' => $item->image_alt ?: $item->title,
                    ];
                }

                return array_merge($item, ['image_url' => null, 'image_alt' => $item['title'] ?? '']);
            })->values()->all();
        }

        $posts = collect($this->defaultPosts());
        if (Schema::hasTable('blog_posts')) {
            $posts = BlogPost::query()
                ->where('status', 'published')
                ->where(function ($query): void {
                    $query->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->get();
        }

        $payload['blog'] = $posts->map(function ($post) {
            if ($post instanceof BlogPost) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'body' => $post->body,
                    'category' => $post->category,
                    'published_at' => optional($post->published_at)->translatedFormat('j F, Y'),
                    'color' => '#A9D3C9',
                    'cover_url' => $post->cover_image ? route('content.blog-cover', $post) : null,
                    'cover_alt' => $post->cover_alt ?: $post->title,
                ];
            }

            return array_merge($post, ['cover_url' => null, 'cover_alt' => $post['title'] ?? '']);
        })->values()->all();

        return $payload;
    }

    public function uniqueSlug(string $title, ?BlogPost $post = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'post-'.Str::lower(Str::random(8));
        }

        $slug = $base;
        $counter = 2;
        while (BlogPost::where('slug', $slug)->when($post, fn ($query) => $query->whereKeyNot($post->getKey()))->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function definition(string $key, string $section, string $label, string $default, string $match, string $type = 'text'): array
    {
        return compact('key', 'section', 'label', 'default', 'match', 'type');
    }

    private function defaultItems(): array
    {
        return [
            'group' => [
                ['title' => '2-3 წელი', 'subtitle' => 'მარიამ ხარაზი', 'body' => 'პირველი ნაბიჯები განვითარებაში — მეტყველება, თამაში და სენსორული აქტივობები.', 'badge' => '2-3', 'color' => '#D3BDD3', 'meta' => ['free' => 3, 'total' => 20, 'schedule' => [['08:00', 'მიღება'], ['09:30', 'საუზმე'], ['10:00', 'სენსორული თამაშები'], ['12:30', 'სადილი'], ['13:00', 'დღის ძილი'], ['16:00', 'თავისუფალი თამაში'], ['18:00', 'გატანა']]]],
                ['title' => '3-4 წელი', 'subtitle' => 'ანა წერეთელი', 'body' => 'დამოუკიდებლობის ჯგუფი — შემოქმედება, საწყისი სწავლა და მეგობრობა.', 'badge' => '3-4', 'color' => '#EFE6A9', 'meta' => ['free' => 2, 'total' => 20, 'schedule' => [['08:00', 'მიღება'], ['09:30', 'საუზმე'], ['10:00', 'შემოქმედებითი აქტივობა'], ['12:30', 'სადილი'], ['13:00', 'დღის ძილი'], ['16:00', 'გარე თამაშები'], ['18:00', 'გატანა']]]],
                ['title' => '4-5 წელი', 'subtitle' => 'თამარ გელაშვილი', 'body' => 'აღმოჩენების ჯგუფი — ბუნება, კითხვა და მუსიკის საწყისები.', 'badge' => '4-5', 'color' => '#A9D3C9', 'meta' => ['free' => 4, 'total' => 20, 'schedule' => [['08:00', 'მიღება'], ['09:30', 'საუზმე'], ['10:00', 'საგანმანათლებლო აქტივობა'], ['12:30', 'სადილი'], ['13:00', 'დასვენება'], ['16:00', 'სპორტული აქტივობა'], ['18:00', 'გატანა']]]],
                ['title' => '5-6 წელი', 'subtitle' => 'ნინო ბერიძე', 'body' => 'სკოლისწინა ჯგუფი — ლოგიკური აზროვნება და საწყისი წერა-კითხვა.', 'badge' => '5-6', 'color' => '#7EB5C1', 'meta' => ['free' => 2, 'total' => 20, 'schedule' => [['08:00', 'მიღება'], ['09:15', 'საუზმე'], ['10:00', 'წერა-კითხვის საწყისები'], ['12:30', 'სადილი'], ['13:00', 'დასვენება'], ['15:30', 'ლოგიკური თამაშები'], ['18:00', 'გატანა']]]],
            ],
            'team' => [
                ['title' => 'ნინო ბერიძე', 'subtitle' => 'დირექტორი', 'body' => 'პედაგოგი 15 წლიანი გამოცდილებით.', 'badge' => 'ნ', 'color' => '#A9D3C9', 'meta' => []],
                ['title' => 'მარიამ ხარაზი', 'subtitle' => 'აღმზრდელი · 2-3 წელი', 'body' => 'ადრეული განვითარების სპეციალისტი.', 'badge' => 'მ', 'color' => '#D3BDD3', 'meta' => []],
                ['title' => 'ანა წერეთელი', 'subtitle' => 'აღმზრდელი · 3-4 წელი', 'body' => 'შემოქმედებითი აქტივობების წამყვანი.', 'badge' => 'ა', 'color' => '#EFE6A9', 'meta' => []],
                ['title' => 'თამარ გელაშვილი', 'subtitle' => 'აღმზრდელი · 4-5 წელი', 'body' => 'მუსიკის და ხელოვნების პედაგოგი.', 'badge' => 'თ', 'color' => '#EFC49A', 'meta' => []],
                ['title' => 'გიორგი ლომიძე', 'subtitle' => 'სპორტის მასწავლებელი', 'body' => 'აქტიური თამაშები და მოძრაობა.', 'badge' => 'გ', 'color' => '#7EB5C1', 'meta' => []],
                ['title' => 'ეკა ონიანი', 'subtitle' => 'ფსიქოლოგი', 'body' => 'ინდივიდუალური მიდგომა თითოეულ ბავშვს.', 'badge' => 'ე', 'color' => '#CCE8C4', 'meta' => []],
            ],
            'faq' => [
                ['title' => 'როგორ ხდება ჩარიცხვა?', 'body' => 'ჩარიცხვის დასაწყებად შეავსეთ ონლაინ განაცხადი. განაცხადის მიღების შემდეგ ჩვენი ადმინისტრაცია დაგიკავშირდებათ, გაგაცნობთ პირობებს და ვიზიტის დროს შეგითანხმებთ.', 'color' => '#EFE6A9', 'meta' => []],
                ['title' => 'რა საბუთებია საჭირო?', 'body' => 'ჩარიცხვისთვის საჭიროა ბავშვის დაბადების მოწმობის ასლი, ჯანმრთელობის ცნობა და მშობლის ან კანონიერი წარმომადგენლის პირადობის დამადასტურებელი დოკუმენტი.', 'color' => '#A9D3C9', 'meta' => []],
                ['title' => 'რომელი მეთოდით ვმუშაობთ?', 'body' => 'სასწავლო პროცესი ეფუძნება თამაშით სწავლებას, მონტესორის მეთოდის ელემენტებსა და ბავშვის საჭიროებებზე მორგებულ მიდგომებს.', 'color' => '#D3BDD3', 'meta' => []],
                ['title' => 'სად შემიძლია საფასურის დეტალების ნახვა?', 'body' => 'სრულ ინფორმაციას მიიღებთ ადმინისტრაციასთან კონსულტაციისას ან ავტორიზაციის შემდეგ — თქვენს პირად პროფილში.', 'color' => '#EFC49A', 'meta' => []],
                ['title' => 'რა არის მშობელთა კლუბი?', 'body' => 'მშობელთა კლუბი არის დახურული სივრცე, რომელიც აერთიანებს მშობლებსა და ბაღის გუნდს.', 'color' => '#CCE8C4', 'meta' => []],
            ],
            'gallery' => [
                ['title' => 'ზაფხულის სახალისო დღე', 'subtitle' => 'ყველა ჯგუფი', 'badge' => '10 ივლისი, 2026', 'color' => '#A9D3C9', 'meta' => []],
                ['title' => 'ხელოვნების გაკვეთილი', 'subtitle' => '3-4 წელი', 'badge' => '5 ივლისი, 2026', 'color' => '#D3BDD3', 'meta' => []],
                ['title' => 'ეზოში თამაშები', 'subtitle' => '2-3 წელი', 'badge' => '1 ივლისი, 2026', 'color' => '#EFE6A9', 'meta' => []],
            ],
        ];
    }

    private function defaultPosts(): array
    {
        return [
            ['title' => 'როგორ ვამზადოთ ბავშვი ბაღისთვის — 5 რჩევა', 'slug' => 'rogor-vamzadot-bavshvi-bagistvis', 'excerpt' => 'პირველი დღეები ბაღში შეიძლება რთული იყოს — ვიზიარებთ პრაქტიკულ რჩევებს.', 'body' => 'პირველი დღეებისთვის წინასწარი მომზადება ბავშვს ახალ გარემოსთან შეგუებაში ეხმარება.', 'category' => 'აღზრდა', 'status' => 'published', 'published_at' => '2026-07-08 09:00:00'],
            ['title' => 'ჯანსაღი კვება პატარებისთვის', 'slug' => 'jansagi-kveba-patarebistvis', 'excerpt' => 'რას ვთავაზობთ ბავშვებს ბაღში და როგორ შევქმნათ თბილი კვების რიტმი სახლშიც.', 'body' => 'კვების მშვიდი და თანმიმდევრული რიტმი ბავშვის კეთილდღეობის მნიშვნელოვანი ნაწილია.', 'category' => 'კვება', 'status' => 'published', 'published_at' => '2026-07-02 09:00:00'],
            ['title' => 'თამაშის მნიშვნელობა 3-4 წლის ასაკში', 'slug' => 'tamashis-mnishvneloba', 'excerpt' => 'თამაშით ბავშვი სოციალურ უნარებს, ენას და ემოციურ ბალანსს იძენს.', 'body' => 'თამაში ბავშვის სწავლებისა და სამყაროს აღმოჩენის ბუნებრივი საშუალებაა.', 'category' => 'განვითარება', 'status' => 'published', 'published_at' => '2026-06-25 09:00:00'],
            ['title' => 'სკოლისთვის მზადება — რას აქცევს ბაღი ყურადღებას', 'slug' => 'skolistvis-mzadeba', 'excerpt' => 'ლოგიკური აზროვნება, ინდივიდუალობა და პასუხისმგებლობა.', 'body' => 'სკოლისთვის მზადება მხოლოდ ასოებისა და ციფრების ცოდნა არ არის.', 'category' => 'სკოლა', 'status' => 'published', 'published_at' => '2026-06-18 09:00:00'],
        ];
    }
}
