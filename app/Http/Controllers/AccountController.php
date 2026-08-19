<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\PrivacyConsent;
use App\Models\User;
use App\Services\MailchimpMarketing;
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
            ->with(['enrollments' => fn ($query) => $query
                ->with([
                    'group',
                    'payments' => fn ($paymentQuery) => $paymentQuery
                        ->whereNotNull('confirmed_at')
                        ->latest('period'),
                ])
                ->latest()])
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

        $familyPayments = $children
            ->flatMap(fn ($child) => $child->enrollments)
            ->flatMap(fn ($enrollment) => $enrollment->payments)
            ->reject(fn ($payment) => in_array($payment->status, ['cancelled', 'waived'], true))
            ->sortByDesc('period')
            ->values();

        return view('account.status', [
            'user' => $user,
            'children' => $children,
            'applications' => $applications,
            'latestEnrollment' => $latestEnrollment,
            'clubAccess' => $user->canAccessParentClub(),
            'marketingConsent' => $this->hasMarketingConsent($user),
            'familyPayments' => $familyPayments,
            'familyOutstanding' => $familyPayments->sum(fn ($payment) => $payment->outstandingAmount()),
            'familyPaid' => $familyPayments->sum(fn ($payment) => (float) $payment->paid_amount),
        ]);
    }

    public function profile(Request $request): View
    {
        $user = $request->user();
        $children = $user->children()
            ->with(['enrollments' => fn ($query) => $query->with('group')->latest()])
            ->orderBy('first_name')
            ->get();

        $user->setRelation('children', $children);

        return view('account.profile', [
            'user' => $user,
            'children' => $children,
        ]);
    }

    public function updateProfile(Request $request, MailchimpMarketing $mailchimp): RedirectResponse
    {
        $user = $request->user();
        $oldEmail = $user->email;
        $hadMarketingConsent = $this->hasMarketingConsent($user);
        $needsChild = ! $user->hasLinkedChild();

        if ($needsChild) {
            $request->merge([
                'child_first_name' => Str::of((string) $request->input('child_first_name'))->squish()->toString(),
                'child_last_name' => Str::of((string) $request->input('child_last_name'))->squish()->toString(),
            ]);
        }

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
            'child_first_name' => [$needsChild ? 'required' : 'nullable', 'string', 'min:2', 'max:100'],
            'child_last_name' => [$needsChild ? 'required' : 'nullable', 'string', 'min:2', 'max:100'],
            'child_birth_date' => [$needsChild ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
        ], [
            'username.required' => 'ჩაწერეთ შესვლის სახელი.',
            'username.min' => 'შესვლის სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'username.unique' => 'ეს შესვლის სახელი უკვე გამოყენებულია.',
            'phone.required' => 'ჩაწერეთ მობილურის ნომერი.',
            'phone.regex' => 'მობილურის ნომერი ჩაწერეთ ფორმატით 5XX XX XX XX.',
            'phone.unique' => 'ეს მობილურის ნომერი უკვე სხვა ანგარიშზეა გამოყენებული.',
            'email.email' => 'ელფოსტის ფორმატი არასწორია.',
            'email.unique' => 'ეს ელფოსტა უკვე სხვა ანგარიშზეა გამოყენებული.',
            'child_first_name.required' => 'ბავშვის მიბმა აუცილებელია — ჩაწერეთ ბავშვის სახელი.',
            'child_first_name.min' => 'ბავშვის სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_last_name.required' => 'ბავშვის მიბმა აუცილებელია — ჩაწერეთ ბავშვის გვარი.',
            'child_last_name.min' => 'ბავშვის გვარი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_birth_date.required' => 'ბავშვის მიბმა აუცილებელია — მიუთითეთ დაბადების თარიღი.',
            'child_birth_date.before_or_equal' => 'ბავშვის დაბადების თარიღი მომავალში ვერ იქნება.',
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

        $newEmail = $validated['email'] ? mb_strtolower(trim($validated['email'])) : null;

        $user->update([
            'username' => $username,
            'name' => trim($validated['name']),
            'phone' => $phone,
            'email' => $newEmail,
            'phone_verified_at' => null,
            'email_verified_at' => null,
        ]);

        AdmissionApplication::query()
            ->where('phone', $phone)
            ->whereNull('guardian_user_id')
            ->update(['guardian_user_id' => $user->id]);

        $childLinked = false;
        if ($needsChild) {
            $this->linkNewChild($request, $user, [
                'child_first_name' => $validated['child_first_name'],
                'child_last_name' => $validated['child_last_name'],
                'child_birth_date' => $validated['child_birth_date'],
            ], true, 'account_profile_required');
            $childLinked = true;
        }

        if ($hadMarketingConsent && $oldEmail !== $newEmail) {
            $mailchimp->unsubscribe($oldEmail);
            $mailchimp->requestDoubleOptIn($user, ['Parent', 'Profile Update']);
        } elseif ($hadMarketingConsent) {
            $mailchimp->syncActiveConsent($user, ['Parent']);
        }

        return redirect()
            ->route('account.status')
            ->with('success', $childLinked
                ? 'პროფილი შენახულია და ბავშვი ანგარიშთან დაკავშირებულია. ადმინისტრატორის ჯგუფში ჩარიცხვის შემდეგ Parent Club ავტომატურად გაიხსნება.'
                : 'პროფილის ინფორმაცია შენახულია.');
    }

    public function storeChild(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['member', 'parent'], true), 403);

        $request->merge([
            'child_first_name' => Str::of((string) $request->input('child_first_name'))->squish()->toString(),
            'child_last_name' => Str::of((string) $request->input('child_last_name'))->squish()->toString(),
        ]);

        $validated = $request->validate([
            'child_first_name' => ['required', 'string', 'min:2', 'max:100'],
            'child_last_name' => ['required', 'string', 'min:2', 'max:100'],
            'child_birth_date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'child_first_name.required' => 'ჩაწერეთ ბავშვის სახელი.',
            'child_first_name.min' => 'ბავშვის სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_last_name.required' => 'ჩაწერეთ ბავშვის გვარი.',
            'child_last_name.min' => 'ბავშვის გვარი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_birth_date.required' => 'მიუთითეთ ბავშვის დაბადების თარიღი.',
            'child_birth_date.before_or_equal' => 'ბავშვის დაბადების თარიღი მომავალში ვერ იქნება.',
        ]);

        $child = $this->linkNewChild($request, $user, $validated, false, 'account_profile_additional');

        return redirect()
            ->route('account.profile')
            ->with('success', "{$child->first_name} {$child->last_name} დაემატა თქვენს ანგარიშს. ადმინისტრატორი შეძლებს მის ცალკე ჯგუფში ჩარიცხვას.");
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

    public function updatePreferences(
        Request $request,
        PrivacyConsentRecorder $recorder,
        MailchimpMarketing $mailchimp,
    ): RedirectResponse {
        $validated = $request->validate([
            'marketing_consent' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $enabled = (bool) $validated['marketing_consent'];
        $hadActiveConsent = $this->hasMarketingConsent($user);

        if ($enabled) {
            $recorder->recordForUserIfMissing(
                $request,
                $user->id,
                'marketing_updates',
                PrivacyPolicy::MARKETING_CONSENT,
                'consent',
                [
                    'source' => 'account_preferences',
                    'channel' => 'email',
                ],
            );

            if (! $user->email) {
                return back()->with('success', 'სიახლეების მიღების თანხმობა შენახულია. ელფოსტის დამატების შემდეგ გამოწერის დადასტურება გახდება შესაძლებელი.');
            }

            $synced = $hadActiveConsent
                ? $mailchimp->syncActiveConsent($user, ['Parent', 'Account Preferences'])
                : $mailchimp->requestDoubleOptIn($user, ['Parent', 'Account Preferences']);

            return back()->with('success', $hadActiveConsent
                ? 'სიახლეების მიღების პარამეტრი შენახულია.'
                : ($synced
                    ? 'დადასტურების წერილი გამოგზავნილია თქვენს ელფოსტაზე. გამოწერა წერილიდან დაადასტურეთ.'
                    : 'სიახლეების მიღების თანხმობა შენახულია, თუმცა ელფოსტის სერვისთან დაკავშირება დროებით ვერ შესრულდა.'));
        }

        PrivacyConsent::query()
            ->where('user_id', $user->id)
            ->where('consent_type', 'marketing_updates')
            ->whereNull('withdrawn_at')
            ->update(['withdrawn_at' => now()]);

        $mailchimp->unsubscribe($user->email);

        return back()->with('success', 'საინფორმაციო და მარკეტინგული წერილების მიღება გამოირთო.');
    }

    private function linkNewChild(
        Request $request,
        User $user,
        array $data,
        bool $onlyIfMissing,
        string $source,
    ): Child {
        return DB::transaction(function () use ($request, $user, $data, $onlyIfMissing, $source): Child {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($onlyIfMissing && $lockedUser->children()->exists()) {
                return $lockedUser->children()->orderBy('children.id')->firstOrFail();
            }

            $duplicate = $lockedUser->children()
                ->where('first_name', $data['child_first_name'])
                ->where('last_name', $data['child_last_name'])
                ->whereDate('birth_date', $data['child_birth_date'])
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'child_first_name' => 'ეს ბავშვი უკვე დაკავშირებულია თქვენს ანგარიშთან.',
                ]);
            }

            $isPrimary = ! $lockedUser->children()->exists();
            $child = Child::query()->create([
                'first_name' => $data['child_first_name'],
                'last_name' => $data['child_last_name'],
                'birth_date' => $data['child_birth_date'],
                'birth_year' => (int) substr($data['child_birth_date'], 0, 4),
            ]);

            $lockedUser->children()->attach($child->id, [
                'relationship' => 'მშობელი',
                'is_primary' => $isPrimary,
                'can_pick_up' => true,
            ]);

            if ($lockedUser->role === 'member') {
                $lockedUser->update(['role' => 'parent']);
            }

            DB::table('audit_logs')->insert([
                'actor_user_id' => $lockedUser->id,
                'action' => 'parent_child.linked_by_parent',
                'subject_type' => Child::class,
                'subject_id' => $child->id,
                'metadata' => json_encode([
                    'parent_user_id' => $lockedUser->id,
                    'source' => $source,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return $child;
        });
    }

    private function hasMarketingConsent(User $user): bool
    {
        return PrivacyConsent::query()
            ->where('user_id', $user->id)
            ->where('consent_type', 'marketing_updates')
            ->whereNull('withdrawn_at')
            ->exists();
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
