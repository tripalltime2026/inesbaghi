<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FamilyController extends Controller
{
    private const RELATIONSHIPS = ['დედა', 'მამა', 'მშობელი', 'კანონიერი წარმომადგენელი', 'ბებია', 'ბაბუა', 'სხვა'];

    public function create(): View
    {
        return view('admin.families.create', [
            'parents' => User::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'phone', 'email']),
            'children' => Child::query()
                ->with('guardians:id,name')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'relationships' => self::RELATIONSHIPS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_name' => ['required_without:parent_id', 'nullable', 'string', 'min:2', 'max:120'],
            'parent_username' => ['required_without:parent_id', 'nullable', 'string', 'min:2', 'max:80'],
            'parent_password' => ['required_without:parent_id', 'nullable', 'string', 'min:8', 'max:128', 'confirmed'],
            'parent_phone' => ['nullable', 'regex:/^(?:\+?995)?5\d{8}$/'],
            'parent_email' => ['nullable', 'email:rfc', 'max:190'],
            'child_id' => ['nullable', 'integer', 'exists:children,id'],
            'child_first_name' => ['required_without:child_id', 'nullable', 'string', 'min:2', 'max:100'],
            'child_last_name' => ['nullable', 'string', 'max:100'],
            'child_birth_date' => ['required_without:child_id', 'nullable', 'date', 'after_or_equal:2017-01-01', 'before_or_equal:today'],
            'relationship' => ['required', Rule::in(self::RELATIONSHIPS)],
            'is_primary' => ['required', 'boolean'],
            'can_pick_up' => ['required', 'boolean'],
            'authority_confirmed' => ['accepted'],
        ], [
            'parent_name.required_without' => 'აირჩიეთ არსებული მშობელი ან ჩაწერეთ ახალი მშობლის სახელი.',
            'parent_username.required_without' => 'ახალი მშობლისთვის ჩაწერეთ შესვლის სახელი.',
            'parent_password.required_without' => 'ახალი მშობლისთვის შექმენით დროებითი პაროლი.',
            'parent_password.min' => 'დროებითი პაროლი მინიმუმ 8 სიმბოლოს უნდა შეიცავდეს.',
            'parent_password.confirmed' => 'დროებითი პაროლები ერთმანეთს არ ემთხვევა.',
            'parent_phone.regex' => 'მობილურის ნომერი ჩაწერეთ ფორმატით 5XX XX XX XX.',
            'child_first_name.required_without' => 'აირჩიეთ არსებული ბავშვი ან ჩაწერეთ ახალი ბავშვის სახელი.',
            'child_birth_date.required_without' => 'ახალი ბავშვისთვის მიუთითეთ დაბადების თარიღი.',
            'authority_confirmed.accepted' => 'დაადასტურეთ, რომ მშობლისა და ბავშვის კავშირი გადამოწმებულია.',
        ]);

        $result = DB::transaction(function () use ($request, $validated): array {
            [$parent, $parentCreated] = $this->resolveParent($validated);
            [$child, $childCreated] = $this->resolveChild($validated);

            if ((bool) $validated['is_primary']) {
                DB::table('child_guardians')
                    ->where('child_id', $child->id)
                    ->update(['is_primary' => false, 'updated_at' => now()]);
            }

            $parent->children()->syncWithoutDetaching([
                $child->id => [
                    'relationship' => $validated['relationship'],
                    'is_primary' => (bool) $validated['is_primary'],
                    'can_pick_up' => (bool) $validated['can_pick_up'],
                ],
            ]);

            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'action' => 'family.guardian_linked',
                'subject_type' => Child::class,
                'subject_id' => $child->id,
                'metadata' => json_encode([
                    'guardian_user_id' => $parent->id,
                    'relationship' => $validated['relationship'],
                    'is_primary' => (bool) $validated['is_primary'],
                    'can_pick_up' => (bool) $validated['can_pick_up'],
                    'parent_created' => $parentCreated,
                    'child_created' => $childCreated,
                    'authority_confirmed' => true,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return compact('parent', 'child', 'parentCreated', 'childCreated');
        });

        $message = match (true) {
            $result['parentCreated'] && $result['childCreated'] => 'მშობლის ანგარიში და ბავშვის პროფილი შეიქმნა და ერთმანეთთან დაკავშირებულია.',
            $result['parentCreated'] => 'მშობლის ანგარიში შეიქმნა და ბავშვთან დაკავშირებულია.',
            $result['childCreated'] => 'ბავშვის პროფილი შეიქმნა და არჩეულ მშობელთან დაკავშირებულია.',
            default => 'მშობელი და ბავშვი წარმატებით დაკავშირებულია.',
        };

        return redirect()
            ->route('admin.children.show', $result['child'])
            ->with('success', $message);
    }

    private function resolveParent(array $validated): array
    {
        if (! empty($validated['parent_id'])) {
            return [User::query()->findOrFail($validated['parent_id']), false];
        }

        $username = Str::of((string) $validated['parent_username'])->squish()->lower()->toString();
        if (User::query()->where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'parent_username' => 'ეს შესვლის სახელი უკვე გამოყენებულია. აირჩიეთ არსებული მშობელი ან სხვა სახელი.',
            ]);
        }

        $phone = filled($validated['parent_phone'] ?? null)
            ? $this->normalizePhone((string) $validated['parent_phone'])
            : null;
        if ($phone && User::query()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'parent_phone' => 'ეს ნომერი უკვე სხვა ანგარიშზეა გამოყენებული. აირჩიეთ არსებული მშობელი.',
            ]);
        }

        $email = filled($validated['parent_email'] ?? null)
            ? mb_strtolower(trim((string) $validated['parent_email']))
            : null;
        if ($email && User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'parent_email' => 'ეს ელფოსტა უკვე სხვა ანგარიშზეა გამოყენებული. აირჩიეთ არსებული მშობელი.',
            ]);
        }

        return [User::create([
            'name' => Str::of((string) $validated['parent_name'])->squish()->toString(),
            'username' => $username,
            'password' => $validated['parent_password'],
            'phone' => $phone,
            'email' => $email,
            'role' => 'member',
            'status' => 'active',
        ]), true];
    }

    private function resolveChild(array $validated): array
    {
        if (! empty($validated['child_id'])) {
            return [Child::query()->findOrFail($validated['child_id']), false];
        }

        $birthDate = (string) $validated['child_birth_date'];

        return [Child::create([
            'first_name' => Str::of((string) $validated['child_first_name'])->squish()->toString(),
            'last_name' => filled($validated['child_last_name'] ?? null)
                ? Str::of((string) $validated['child_last_name'])->squish()->toString()
                : null,
            'birth_date' => $birthDate,
            'birth_year' => (int) substr($birthDate, 0, 4),
        ]), true];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '995')) {
            $digits = substr($digits, 3);
        }

        return '+995'.$digits;
    }
}
