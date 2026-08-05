<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\PrivacyConsent;
use App\Models\User;
use App\Services\MailchimpMarketing;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('account.status', [
            'user' => $user,
            'children' => $children,
            'applications' => $applications,
            'latestEnrollment' => $latestEnrollment,
            'clubAccess' => $user->canAccessParentClub(),
            'marketingConsent' => $this->hasMarketingConsent($user),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request, MailchimpMarketing $mailchimp): RedirectResponse
    {
        $user = $request->user();
        $oldEmail = $user->email;
        $hadMarketingConsent = $this->hasMarketingConsent($user);

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

        if ($hadMarketingConsent && $oldEmail !== $newEmail) {
            $mailchimp->unsubscribe($oldEmail);
            $mailchimp->requestDoubleOptIn($user, ['Parent', 'Profile Update']);
        } elseif ($hadMarketingConsent) {
            $mailchimp->syncActiveConsent($user, ['Parent']);
        }

        return redirect()
            ->route('account.status')
            ->with('success', 'პროფილის ინფორმაცია შენახულია.');
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
            if (! $user->email) {
                return back()->withErrors([
                    'marketing_consent' => 'სიახლეების ელფოსტაზე მისაღებად ჯერ პროფილში დაამატეთ ელფოსტა.',
                ]);
            }

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
