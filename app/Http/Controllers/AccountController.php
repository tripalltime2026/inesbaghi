<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\PrivacyConsent;
use App\Models\User;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $children = $user->children()
            ->with(['enrollments' => fn ($query) => $query->with('group')->latest()])
            ->orderBy('first_name')
            ->get();

        $user->setRelation('children', $children);

        $applications = AdmissionApplication::query()
            ->where(function ($query) use ($user): void {
                $query->where('guardian_user_id', $user->id);
                if ($user->phone) {
                    $query->orWhere('phone', $user->phone);
                }
            })
            ->latest()
            ->get();

        $latestEnrollment = $children
            ->flatMap(fn ($child) => $child->enrollments)
            ->sortByDesc('created_at')
            ->first();

        $marketingConsent = PrivacyConsent::query()
            ->where('user_id', $user->id)
            ->where('consent_type', 'marketing_updates')
            ->whereNull('withdrawn_at')
            ->exists();

        return view('account.status', [
            'user' => $user,
            'children' => $children,
            'applications' => $applications,
            'latestEnrollment' => $latestEnrollment,
            'clubAccess' => $user->canAccessParentClub(),
            'marketingConsent' => $marketingConsent,
        ]);
    }

    public function profile(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
            'children' => $request->user()->children()
                ->with(['enrollments.group'])
                ->orderBy('first_name')
                ->get(),
            'guardianConfirmation' => PrivacyPolicy::GUARDIAN_CONFIRMATION,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:2',
                'max:80',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => [
                'required',
                'regex:/^(?:\+?995)?5\d{8}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email:rfc',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'username.required' => 'ჩაწერეთ შესვლის სახელი.',
            'username.min' => 'შესვლის სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'username.unique' => 'ეს შესვლის სახელი უკვე გამოყენებულია.',
            'phone.required' => 'ჩაწერეთ მობილურის ნომერი.',
            'phone.regex' => 'მობილურის ნომერი ჩაწერეთ ფორმატით 5XX XX XX XX.',
            'phone.unique' => 'ეს მობილურის ნომერი უკვე სხვა ანგარიშზეა გამოყენებული.',
            'email.email' => 'ელფოსტის ფორმატი არასწორია.',
            'email.unique' => 'ეს ელფოსტა უკვე სხვა ანგარიშზეა გამოყენებული.',
        ]);

        $username = Str::of($validated['username'])->squish()->lower()->toString();
        $usernameOwner = User::query()
            ->where('username', $username)
            ->whereKeyNot($user->id)
            ->exists();
        if ($usernameOwner) {
            throw ValidationException::withMessages([
                'username' => 'ეს შესვლის სახელი უკვე გამოყენებულია.',
            ]);
        }

        $phone = $this->normalizePhone($validated['phone']);
        $phoneOwner = User::query()
            ->where('phone', $phone)
            ->whereKeyNot($user->id)
            ->exists();

        if ($phoneOwner) {
            throw ValidationException::withMessages([
                'phone' => 'ეს მობილურის ნომერი უკვე სხვა ანგარიშზეა გამოყენებული.',
            ]);
        }

        $user->update([
            'username' => $username,
            'name' => trim($validated['name']),
            'phone' => $phone,
            'email' => $validated['email'] ? mb_strtolower(trim($validated['email'])) : null,
            'phone_verified_at' => null,
            'email_verified_at' => null,
        ]);

        AdmissionApplication::query()
            ->where('phone', $phone)
            ->whereNull('guardian_user_id')
            ->update(['guardian_user_id' => $user->id]);

        return redirect()
            ->route('account.status')
            ->with('success', 'პროფილის ინფორმაცია შენახულია.');
    }

    public function storeChild(Request $request, PrivacyConsentRecorder $recorder): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'after_or_equal:2017-01-01', 'before_or_equal:today'],
            'relationship' => ['required', Rule::in(['დედა', 'მამა', 'მშობელი', 'კანონიერი წარმომადგენელი'])],
            'can_pick_up' => ['required', 'boolean'],
            'guardian_confirmation' => ['accepted'],
        ], [
            'first_name.required' => 'ჩაწერეთ ბავშვის სახელი.',
            'birth_date.required' => 'მიუთითეთ ბავშვის დაბადების თარიღი.',
            'birth_date.after_or_equal' => 'შეამოწმეთ ბავშვის დაბადების თარიღი.',
            'birth_date.before_or_equal' => 'დაბადების თარიღი მომავალში ვერ იქნება.',
            'guardian_confirmation.accepted' => 'ბავშვის რეგისტრაციისთვის დაადასტურეთ, რომ მისი კანონიერი წარმომადგენელი ხართ.',
        ]);

        $user = $request->user();
        $firstName = Str::of($validated['first_name'])->squish()->toString();
        $lastName = Str::of((string) ($validated['last_name'] ?? ''))->squish()->toString();
        $birthDate = (string) $validated['birth_date'];

        $duplicate = $user->children()
            ->get(['children.id', 'children.first_name', 'children.last_name', 'children.birth_date'])
            ->contains(fn (Child $child): bool =>
                mb_strtolower(trim($child->first_name)) === mb_strtolower($firstName)
                && mb_strtolower(trim((string) $child->last_name)) === mb_strtolower($lastName)
                && $child->birth_date?->toDateString() === $birthDate
            );

        if ($duplicate) {
            throw ValidationException::withMessages([
                'first_name' => 'ეს ბავშვი უკვე დაკავშირებულია თქვენს ანგარიშთან.',
            ]);
        }

        DB::transaction(function () use ($request, $recorder, $user, $validated, $firstName, $lastName, $birthDate): void {
            $child = Child::create([
                'first_name' => $firstName,
                'last_name' => $lastName !== '' ? $lastName : null,
                'birth_date' => $birthDate,
                'birth_year' => (int) substr($birthDate, 0, 4),
            ]);

            $user->children()->attach($child->id, [
                'relationship' => $validated['relationship'],
                'is_primary' => true,
                'can_pick_up' => (bool) $validated['can_pick_up'],
            ]);

            $recorder->record(
                $request,
                'child_profile_guardian_confirmation',
                PrivacyPolicy::GUARDIAN_CONFIRMATION,
                'consent',
                $user->id,
                Child::class,
                $child->id,
                [
                    'source' => 'account_child_registration',
                    'relationship' => $validated['relationship'],
                ],
            );

            DB::table('audit_logs')->insert([
                'actor_user_id' => $user->id,
                'action' => 'child.created_by_guardian',
                'subject_type' => Child::class,
                'subject_id' => $child->id,
                'metadata' => json_encode([
                    'relationship' => $validated['relationship'],
                    'can_pick_up' => (bool) $validated['can_pick_up'],
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()
            ->route('account.profile', ['#children'])
            ->with('success', 'ბავშვის პროფილი შეიქმნა და თქვენს ანგარიშთან დაკავშირებულია. ჩარიცხვა ადმინისტრაციის დადასტურების შემდეგ გააქტიურდება.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => [$user->password ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ], [
            'current_password.required' => 'ჩაწერეთ მიმდინარე პაროლი.',
            'password.min' => 'ახალი პაროლი მინიმუმ 8 სიმბოლოს უნდა შეიცავდეს.',
            'password.confirmed' => 'ახალი პაროლები ერთმანეთს არ ემთხვევა.',
        ]);

        if ($user->password && ! Hash::check((string) ($validated['current_password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'მიმდინარე პაროლი არასწორია.',
            ]);
        }

        $user->update(['password' => $validated['password']]);

        return back()->with('success', 'პაროლი წარმატებით შენახულია.');
    }

    public function updatePreferences(Request $request, PrivacyConsentRecorder $recorder): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_consent' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $enabled = (bool) $validated['marketing_consent'];

        if ($enabled) {
            $recorder->recordForUserIfMissing(
                $request,
                $user->id,
                'marketing_updates',
                PrivacyPolicy::MARKETING_CONSENT,
                'consent',
                ['source' => 'account_preferences', 'phone' => $user->phone],
            );
        } else {
            PrivacyConsent::query()
                ->where('user_id', $user->id)
                ->where('consent_type', 'marketing_updates')
                ->whereNull('withdrawn_at')
                ->update(['withdrawn_at' => now()]);
        }

        return back()->with('success', $enabled
            ? 'საინფორმაციო და მარკეტინგული შეტყობინებები ჩაირთო.'
            : 'საინფორმაციო და მარკეტინგული შეტყობინებები გამოირთო.');
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
