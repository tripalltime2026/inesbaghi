<?php

namespace App\Services;

use App\Models\KindergartenGroup;
use App\Models\SupportConversation;
use App\Models\SupportKnowledgeArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class InesAiService
{
    public function respond(SupportConversation $conversation, string $message): array
    {
        $context = $conversation->context ?? [];
        $normalized = $this->normalize($message);
        $context = $this->captureContext($context, $normalized);

        if ($this->matches($normalized, ['ადმინისტრატორი', 'ოპერატორი', 'ადამიანთან', 'თანამშრომელთან', 'დამაკავშირეთ', 'დამაკავშირე'])) {
            return $this->result(
                'თქვენი შეტყობინება ადმინისტრატორს გადავეცი. პასუხს ამავე ჩატში მიიღებთ.',
                $context,
                true,
                'ადმინისტრატორთან დაკავშირება',
            );
        }

        if ($this->isSensitiveOrDecisionRequest($normalized)) {
            return $this->result(
                'ამ საკითხზე ზუსტი პასუხი ადმინისტრაციის დადასტურებას საჭიროებს. შეტყობინებას გადავცემ და პასუხს ამავე ჩატში მიიღებთ.',
                $context,
                true,
                'ადმინისტრაციული საკითხი',
            );
        }

        if ($this->isAvailabilityIntent($normalized) || ($context['flow'] ?? null) === 'availability') {
            return $this->availabilityResponse($context);
        }

        if ($this->matches($normalized, ['რომელი ასაკიდან', 'რამდენი წლიდან', 'რა ასაკიდან', 'მიღება იწყება', 'ასაკობრივი ჯგუფ'])) {
            return $this->intakeAgeResponse($context);
        }

        if ($this->matches($normalized, ['რით გამოირჩევა', 'განსხვავება', 'როგორი ბაღია', 'ბაღის სტილი', 'როგორი სტილი', 'მეთოდოლოგია', 'მონტესორი', 'როგორ ასწავლით'])) {
            $key = $this->matches($normalized, ['სტილი', 'მეთოდოლოგია', 'მონტესორი', 'როგორ ასწავლით'])
                ? 'teaching_style'
                : 'kindergarten_identity';

            return $this->result($this->knowledge($key), $context, false, 'ბაღის შესახებ');
        }

        if ($this->matches($normalized, ['ვიზიტი', 'ტური', 'დათვალიერება', 'მოსვლა'])) {
            return $this->result(
                $this->knowledge('introductory_visit').' გსურთ, ადმინისტრატორს გადავცე ვიზიტის მოთხოვნა?',
                array_merge($context, ['suggested_action' => 'visit']),
                false,
                'გაცნობითი ვიზიტი',
            );
        }

        if ($this->matches($normalized, ['დოკუმენტ', 'საბუთ'])) {
            return $this->result($this->knowledge('documents'), $context, false, 'საჭირო დოკუმენტები');
        }

        if ($this->matches($normalized, ['ჩარიცხვა', 'რეგისტრაცია', 'განაცხად'])) {
            return $this->result(
                $this->knowledge('admission_process').' მითხარით, ჩარიცხვა მიმდინარე თუ მომდევნო სასწავლო წლისთვის გსურთ?',
                array_merge($context, ['flow' => 'availability', 'awaiting' => 'academic_year']),
                false,
                'ჩარიცხვა',
            );
        }

        $enhanced = $this->openAiResponse($conversation, $message, $context);
        if ($enhanced !== null) {
            return $this->result(
                $enhanced,
                $context,
                str_contains($this->normalize($enhanced), 'ადმინისტრატორს გადავცემ'),
                'ზოგადი კითხვა',
                ['provider' => 'openai'],
            );
        }

        return $this->result(
            'ამ კითხვაზე ზუსტი პასუხის მოსამზადებლად ადმინისტრატორის დახმარებაა საჭირო. შეტყობინებას გადავცემ და პასუხს ამავე ჩატში მიიღებთ.',
            $context,
            true,
            'ზოგადი კითხვა',
        );
    }

    public function adminDraft(SupportConversation $conversation): string
    {
        $lastUserMessage = $conversation->messages()
            ->where('sender_type', 'user')
            ->latest('id')
            ->value('body');

        if (! $lastUserMessage) {
            return 'გამარჯობა, როგორ შეგვიძლია დაგეხმაროთ?';
        }

        $context = $conversation->context ?? [];
        $ai = $this->openAiResponse(
            $conversation,
            "მოამზადე ადმინისტრატორის პასუხის მოკლე მონახაზი ამ შეტყობინებაზე: {$lastUserMessage}",
            array_merge($context, ['admin_draft' => true]),
        );

        if ($ai !== null) {
            return $ai;
        }

        return 'გამარჯობა, მადლობა რომ მოგვწერეთ. თქვენს საკითხს ვამოწმებთ და დაზუსტებულ პასუხს მალე მოგწერთ.';
    }

    private function availabilityResponse(array $context): array
    {
        $context['flow'] = 'availability';
        $academicYear = $context['academic_year'] ?? null;

        if (! $academicYear) {
            $context['awaiting'] = 'academic_year';

            return $this->result(
                'რომელი სასწავლო წლისთვის განიხილავთ ჩარიცხვას — '.$this->currentAcademicYear().' თუ '.$this->nextAcademicYear().'?',
                $context,
                false,
                'ჯგუფში ადგილის შემოწმება',
            );
        }

        $groupSlug = $context['group_slug'] ?? null;
        if (! $groupSlug && isset($context['birth_year'])) {
            $groupSlug = $this->groupSlugForBirthYear((int) $context['birth_year'], $academicYear);
            $context['group_slug'] = $groupSlug;
        }

        if (! $groupSlug) {
            $context['awaiting'] = 'birth_year';

            return $this->result(
                'ბავშვის დაბადების წელი რომელია? ამის მიხედვით შესაბამის ასაკობრივ ჯგუფს და ადგილის სტატუსს შევამოწმებ.',
                $context,
                false,
                'ჯგუფში ადგილის შემოწმება',
            );
        }

        $group = KindergartenGroup::query()
            ->where('slug', $groupSlug)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->first();

        if (! $group) {
            unset($context['awaiting']);

            return $this->result(
                "{$academicYear} სასწავლო წლის {$groupSlug} წლის ჯგუფის საბოლოო ადგილები სისტემაში ჯერ არ არის გამოქვეყნებული. ადმინისტრატორს გადავცემ, რომ ხელმისაწვდომობა დაგიდასტუროთ.",
                $context,
                true,
                'ჯგუფში ადგილის შემოწმება',
            );
        }

        $active = $group->enrollments()->where('status', 'active')->count();
        $pending = $group->enrollments()->where('status', 'pending')->count();
        $available = max(0, (int) $group->capacity - $active);
        unset($context['awaiting']);

        if ($available === 0) {
            $body = "{$academicYear} სასწავლო წლის {$group->name} ჯგუფში ამჟამად თავისუფალი ადგილი აღარ ჩანს. შეგიძლიათ დატოვოთ განაცხადი მოლოდინის სიაში ან მოითხოვოთ ადმინისტრატორთან დაკავშირება.";
        } elseif ($pending >= $available) {
            $body = "{$academicYear} სასწავლო წლის {$group->name} ჯგუფში ადგილი ჯერ კიდევ ჩანს, თუმცა მოთხოვნა მაღალია და {$pending} განაცხადი განხილვის პროცესშია. ადგილის გარანტია მხოლოდ ადმინისტრაციის დადასტურების შემდეგ მოქმედებს.";
        } elseif (config('services.ines_ai.show_exact_availability')) {
            $body = "{$academicYear} სასწავლო წლის {$group->name} ჯგუფში ამჟამად {$available} თავისუფალი ადგილი ჩანს. საბოლოო დადასტურებისთვის შეგიძლიათ შეავსოთ განაცხადი ან დაგეგმოთ ვიზიტი.";
        } else {
            $body = "{$academicYear} სასწავლო წლის {$group->name} ჯგუფში ამჟამად თავისუფალი ადგილი ჩანს. საბოლოო დადასტურებისთვის შეგიძლიათ შეავსოთ განაცხადი ან დაგეგმოთ გაცნობითი ვიზიტი.";
        }

        return $this->result($body, $context, false, 'ჯგუფში ადგილის შემოწმება', [
            'group_id' => $group->id,
            'capacity' => (int) $group->capacity,
            'active' => $active,
            'pending' => $pending,
            'available' => $available,
            'academic_year' => $academicYear,
        ]);
    }

    private function intakeAgeResponse(array $context): array
    {
        $groups = KindergartenGroup::query()
            ->where('is_active', true)
            ->orderBy('age_min_months')
            ->get(['name', 'slug', 'age_min_months', 'age_max_months'])
            ->unique('slug');

        if ($groups->isEmpty()) {
            return $this->result(
                'მიღების ასაკობრივი პირობები ადმინისტრატორთან უნდა დაზუსტდეს. შეტყობინებას გადავცემ.',
                $context,
                true,
                'მიღების ასაკი',
            );
        }

        $minimumMonths = (int) $groups->min('age_min_months');
        $minimumYears = $minimumMonths / 12;
        $minimumLabel = fmod($minimumYears, 1.0) === 0.0
            ? (int) $minimumYears.' წლიდან'
            : number_format($minimumYears, 1).' წლიდან';
        $labels = $groups->pluck('slug')->map(fn (string $slug) => $slug.' წლის')->implode(', ');

        return $this->result(
            "მიღება იწყება {$minimumLabel}. ამჟამად მოქმედი ასაკობრივი ჯგუფებია: {$labels}. მითხარით ბავშვის დაბადების წელი და სასწავლო წელი, რათა შესაბამისი ჯგუფი და ადგილი შევამოწმო.",
            array_merge($context, ['flow' => 'availability', 'awaiting' => 'academic_year']),
            false,
            'მიღების ასაკი',
        );
    }

    private function captureContext(array $context, string $message): array
    {
        $academicYear = $this->extractAcademicYear($message, $context['awaiting'] ?? null);
        if ($academicYear) {
            $context['academic_year'] = $academicYear;
        }

        if (preg_match('/\b([2-5])\s*[-–]\s*([3-6])\b/u', $message, $matches)) {
            $context['group_slug'] = $matches[1].'-'.$matches[2];
        }

        $birthYear = $this->extractBirthYear($message, $context['awaiting'] ?? null);
        if ($birthYear) {
            $context['birth_year'] = $birthYear;
        }

        return $context;
    }

    private function extractAcademicYear(string $message, ?string $awaiting): ?string
    {
        if (preg_match('/\b(20\d{2})\s*[-–]\s*(20\d{2})\b/u', $message, $matches)) {
            return $matches[1].'-'.$matches[2];
        }

        if ($this->matches($message, ['მომდევნო', 'შემდეგი წლის', 'შემდგომი წლის'])) {
            return $this->nextAcademicYear();
        }

        if ($this->matches($message, ['ამ წლის', 'მიმდინარე წლის', 'ახლანდელი წლის'])) {
            return $this->currentAcademicYear();
        }

        if ($awaiting === 'academic_year' && preg_match('/\b(20\d{2})\b/u', $message, $matches)) {
            $start = (int) $matches[1];
            if ($start >= now()->year && $start <= now()->year + 2) {
                return $start.'-'.($start + 1);
            }
        }

        return null;
    }

    private function extractBirthYear(string $message, ?string $awaiting): ?int
    {
        if (! preg_match_all('/\b(20\d{2})\b/u', $message, $matches)) {
            return null;
        }

        foreach ($matches[1] as $value) {
            $year = (int) $value;
            $looksLikeBirthYear = $year >= now()->year - 8 && $year <= now()->year;
            if ($looksLikeBirthYear && ($awaiting === 'birth_year' || $this->matches($message, ['დაბად', 'ბავშვი', 'წლისაა']))) {
                return $year;
            }
        }

        if ($awaiting === 'birth_year') {
            $year = (int) end($matches[1]);
            return $year >= now()->year - 8 && $year <= now()->year ? $year : null;
        }

        return null;
    }

    private function groupSlugForBirthYear(int $birthYear, string $academicYear): string
    {
        $startYear = (int) Str::before($academicYear, '-');
        $age = max(2, min(5, $startYear - $birthYear));

        return $age.'-'.($age + 1);
    }

    private function isAvailabilityIntent(string $message): bool
    {
        return $this->matches($message, ['არის ადგილი', 'ადგილი გაქვთ', 'თავისუფალი ადგილი', 'ადგილებია', 'ჯგუფში ადგილი', 'ვაკანსია']);
    }

    private function isSensitiveOrDecisionRequest(string $message): bool
    {
        return $this->matches($message, [
            'ფასი', 'საფასური', 'ფასდაკლება', 'გადახდა', 'დავალიანება', 'ქვითარი',
            'ჩემი ბავშვი', 'ჩემი შვილი', 'სამედიცინო', 'ჯანმრთელობა', 'ალერგია',
            'საჩივარი', 'უკმაყოფილო', 'გარანტია', 'დამიდასტურეთ', 'დადასტურებულია',
        ]);
    }

    private function knowledge(string $key): string
    {
        return SupportKnowledgeArticle::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->value('content')
            ?? 'ამ ინფორმაციას ადმინისტრატორთან დავაზუსტებთ.';
    }

    private function openAiResponse(SupportConversation $conversation, string $message, array $context): ?string
    {
        $apiKey = (string) config('services.ines_ai.api_key');
        if ($apiKey === '' || ! config('services.ines_ai.enabled')) {
            return null;
        }

        $knowledge = SupportKnowledgeArticle::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('content', 'title')
            ->map(fn (string $content, string $title) => "{$title}: {$content}")
            ->implode("\n");

        $groups = KindergartenGroup::query()
            ->where('is_active', true)
            ->withCount([
                'enrollments as active_count' => fn ($query) => $query->where('status', 'active'),
                'enrollments as pending_count' => fn ($query) => $query->where('status', 'pending'),
            ])
            ->get()
            ->map(function (KindergartenGroup $group): string {
                $available = max(0, (int) $group->capacity - (int) $group->active_count);
                return "{$group->academic_year}; {$group->name}; slug={$group->slug}; capacity={$group->capacity}; active={$group->active_count}; pending={$group->pending_count}; available={$available}";
            })
            ->implode("\n");

        $instructions = <<<'PROMPT'
თქვენ ხართ Ines AI — „ინეს ბაღის“ ციფრული ასისტენტი. უპასუხეთ ქართულად, თბილად, პროფესიონალურად და მოკლედ.
გამოიყენეთ მხოლოდ ქვემოთ მოწოდებული დამტკიცებული ცოდნა და ცოცხალი ჯგუფების მონაცემები.
არ გამოიგონოთ ფასი, ადგილი, თარიღი, პოლიტიკა ან ბავშვის პირადი ინფორმაცია.
თავისუფალი ადგილი საბოლოოდ გარანტირებული არასდროს არის ადმინისტრაციის დადასტურების გარეშე.
არ მოითხოვოთ ან არ გაიმეოროთ სამედიცინო, პირადობის, გადახდის ან სხვა განსაკუთრებული კატეგორიის მონაცემები.
თუ პასუხი მოწოდებულ ფაქტებში არ არის, თქვით: „ამ საკითხს ადმინისტრატორს გადავცემ. პასუხს ამავე ჩატში მიიღებთ.“
არ გამოიყენოთ Markdown-ის ცხრილები. პასუხი მაქსიმუმ 120 სიტყვა იყოს.
PROMPT;

        $input = "დამტკიცებული ცოდნა:\n{$knowledge}\n\nცოცხალი ჯგუფების მონაცემები:\n{$groups}\n\nსაუბრის კონტექსტი: ".json_encode($context, JSON_UNESCAPED_UNICODE)."\n\nმომხმარებლის შეტყობინება: {$message}";

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(15)
                ->retry(1, 250)
                ->post(rtrim((string) config('services.ines_ai.base_url'), '/').'/v1/responses', [
                    'model' => config('services.ines_ai.model'),
                    'store' => false,
                    'instructions' => $instructions,
                    'input' => $input,
                    'max_output_tokens' => 350,
                ]);

            if (! $response->successful()) {
                Log::warning('Ines AI API request failed', [
                    'status' => $response->status(),
                    'conversation_id' => $conversation->id,
                ]);
                return null;
            }

            return $this->extractOutputText($response->json());
        } catch (Throwable $exception) {
            Log::warning('Ines AI API unavailable', [
                'conversation_id' => $conversation->id,
                'exception' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    private function extractOutputText(array $payload): ?string
    {
        if (isset($payload['output_text']) && is_string($payload['output_text'])) {
            return trim($payload['output_text']);
        }

        foreach ($payload['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    return trim($content['text']);
                }
            }
        }

        return null;
    }

    private function currentAcademicYear(): string
    {
        $start = now()->month >= 7 ? now()->year : now()->year - 1;
        return $start.'-'.($start + 1);
    }

    private function nextAcademicYear(): string
    {
        $start = (int) Str::before($this->currentAcademicYear(), '-');
        return ($start + 1).'-'.($start + 2);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    private function matches(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function result(string $body, array $context, bool $escalate, string $topic, array $metadata = []): array
    {
        return compact('body', 'context', 'escalate', 'topic', 'metadata');
    }
}
