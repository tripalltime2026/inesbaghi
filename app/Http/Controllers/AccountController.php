<?php

namespace App\Http\Controllers;

use App\Models\AdmissionApplication;
use App\Models\PrivacyConsent;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
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
            'phone.required' => 'ჩაწერეთ მობილურის ნომერი.',
            'phone.regex' => 'მობილურის ნომერი ჩაწერეთ ფორმატით 5XX XX XX XX.',
            'phone.unique' => 'ეს მობილურის ნომერი უკვე სხვა ანგარიშზეა გამოყენებული.',
            'email.email' => 'ელფოსტის ფორმატი არასწორია.',
            'email.unique' => 'ეს ელფოსტა უკვე სხვა ანგარიშზეა გამოყენებული.',
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $phoneOwner = \App\Models\User::query()
            ->where('phone', $phone)
            ->whereKeyNot($user->id)
            ->exists();

        if ($phoneOwner) {
            throw ValidationException::withMessages([
                'phone' => 'ეს მობილურის ნომერი უკვე სხვა ანგარიშზეა გამოყენებული.',
            ]);
        }

        $user->update([
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

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ], [
            'current_password.required' => 'ჩაწერეთ მიმდინარე პაროლი.',
            'password.min' => 'ახალი პაროლი მინიმუმ 8 სიმბოლოს უნდა შეიცავდეს.',
            'password.confirmed' => 'ახალი პაროლები ერთმანეთს არ ემთხვევა.',
        ]);

        $user = $request->user();
        if (! $user->password || ! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'მიმდინარე პაროლი არასწორია.',
            ]);
        }

        $user->update(['password' => $validated['password']]);

        return back()->with('success', 'პაროლი წარმატებით შეიცვალა.');
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
