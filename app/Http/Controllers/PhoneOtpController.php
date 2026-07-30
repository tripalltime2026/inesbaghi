<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Models\PrivacyConsent;
use App\Models\User;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PhoneOtpController extends Controller
{
    public function mode(): JsonResponse
    {
        return response()->json([
            'demo_enabled' => (bool) config('services.demo_auth.enabled', false),
            'demo_login_url' => route('auth.demo'),
            'admin_phone' => config('services.demo_auth.admin_phone', '555411831'),
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ]);
    }

    public function demoLogin(Request $request, PrivacyConsentRecorder $recorder): JsonResponse
    {
        abort_unless((bool) config('services.demo_auth.enabled', false), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^(?:\+?995)?5\d{8}$/'],
            'privacy_accepted' => ['nullable', 'boolean'],
            'marketing_consent' => ['nullable', 'boolean'],
            'privacy_policy_version' => ['nullable', 'string', 'max:32'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $adminPhone = $this->normalizePhone((string) config('services.demo_auth.admin_phone', '555411831'));
        $isDemoAdmin = hash_equals($adminPhone, $phone);
        $user = User::where('phone', $phone)->first();
        $privacyRequired = ! $isDemoAdmin && ! $this->hasCurrentAccountPrivacy($user);

        if ($privacyRequired) {
            $this->assertPrivacyAcceptance($request);
        }

        if (! $user) {
            $user = User::create([
                'name' => $isDemoAdmin ? 'ადმინისტრატორი' : $validated['name'],
                'phone' => $phone,
                'role' => $isDemoAdmin ? 'admin' : 'member',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);
        } elseif ($isDemoAdmin) {
            $user->update([
                'role' => 'admin',
                'status' => 'active',
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ]);
        } else {
            $user->phone_verified_at ??= now();
            if (! $user->name) {
                $user->name = $validated['name'];
            }
            $user->save();
        }

        if ($privacyRequired) {
            $this->recordAccountPrivacy(
                $request,
                $recorder,
                $user,
                $request->boolean('marketing_consent'),
                ['demo' => true, 'registration' => true],
            );
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->loginResponse($user, true);
    }

    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^(?:\+?995)?5\d{8}$/'],
            'privacy_accepted' => ['nullable', 'boolean'],
            'marketing_consent' => ['nullable', 'boolean'],
            'privacy_policy_version' => ['nullable', 'string', 'max:32'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $user = User::where('phone', $phone)->first();
        $privacyRequired = ! $this->hasCurrentAccountPrivacy($user);

        if ($privacyRequired) {
            $this->assertPrivacyAcceptance($request);
        }

        $key = 'otp:'.$phone.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['phone' => 'ძალიან ბევრი მოთხოვნაა. სცადეთ მოგვიანებით.']);
        }

        RateLimiter::hit($key, 60);
        OtpCode::where('phone', $phone)->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);
        $otp = OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(config('services.sms.otp_ttl_minutes', 5)),
            'request_ip' => $request->ip(),
        ]);

        $request->session()->put('privacy_registration.'.$otp->id, [
            'required' => $privacyRequired,
            'policy_version' => $privacyRequired ? PrivacyPolicy::VERSION : null,
            'marketing_consent' => $privacyRequired && $request->boolean('marketing_consent'),
            'accepted_at' => $privacyRequired ? now()->toIso8601String() : null,
        ]);

        Log::info('OTP requested', [
            'phone' => $phone,
            'otp_id' => $otp->id,
            'code' => app()->isLocal() ? $code : 'hidden',
            'registration' => $privacyRequired,
        ]);

        $payload = ['request_id' => $otp->id, 'expires_in' => 300, 'registration' => $privacyRequired];
        if (app()->environment(['local', 'testing']) && config('app.debug')) {
            $payload['debug_code'] = $code;
        }

        return response()->json($payload);
    }

    public function verify(Request $request, PrivacyConsentRecorder $recorder): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $privacy = $request->session()->get('privacy_registration.'.$validated['request_id']);
        if (! is_array($privacy)) {
            throw ValidationException::withMessages(['phone' => 'შესვლის მოთხოვნა აღარ არის მოქმედი. თავიდან მოითხოვეთ კოდი.']);
        }

        if (($privacy['required'] ?? false) && ($privacy['policy_version'] ?? null) !== PrivacyPolicy::VERSION) {
            throw ValidationException::withMessages(['privacy_accepted' => 'ახალი ანგარიშის შესაქმნელად საჭიროა მოქმედი კონფიდენციალურობის პირობების დადასტურება.']);
        }

        $otp = OtpCode::whereKey($validated['request_id'])->where('phone', $phone)->first();

        if (! $otp || ! $otp->usable() || $otp->attempts >= config('services.sms.otp_max_attempts', 5)) {
            throw ValidationException::withMessages(['code' => 'კოდი არასწორია ან ვადა გაუვიდა.']);
        }

        $otp->increment('attempts');
        if (! Hash::check($validated['code'], $otp->code_hash)) {
            throw ValidationException::withMessages(['code' => 'კოდი არასწორია.']);
        }

        $otp->update(['consumed_at' => now()]);

        $user = User::where('phone', $phone)->first();
        $privacyRequired = (bool) ($privacy['required'] ?? false);

        if (! $user) {
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $phone,
                'role' => 'member',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);
        } else {
            $user->phone_verified_at ??= now();
            $user->save();
        }

        if ($privacyRequired) {
            $this->recordAccountPrivacy(
                $request,
                $recorder,
                $user,
                (bool) ($privacy['marketing_consent'] ?? false),
                ['otp_request_id' => $otp->id, 'registration' => true],
            );
        } elseif (! $this->hasCurrentAccountPrivacy($user)) {
            throw ValidationException::withMessages(['privacy_accepted' => 'ანგარიშის კონფიდენციალურობის დადასტურება აღარ არის მოქმედი. დაიწყეთ რეგისტრაცია თავიდან.']);
        }

        $request->session()->forget('privacy_registration.'.$validated['request_id']);

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->loginResponse($user);
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('home');
    }

    private function loginResponse(User $user, bool $demo = false): JsonResponse
    {
        $redirectTo = match (true) {
            $user->hasRole('admin') => route('admin.dashboard'),
            $user->hasRole('finance') => route('admin.payments.index'),
            $user->hasRole('teacher') => route('admin.attendance.index'),
            $user->canAccessParentClub() => route('parent.dashboard'),
            default => route('account.status'),
        };

        return response()->json([
            'user' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'membership' => $user->membershipLabel(),
                'parent_club_access' => $user->canAccessParentClub(),
            ],
            'demo' => $demo,
            'redirect_to' => $redirectTo,
        ]);
    }

    private function assertPrivacyAcceptance(Request $request): void
    {
        if (! $request->boolean('privacy_accepted') || $request->input('privacy_policy_version') !== PrivacyPolicy::VERSION) {
            throw ValidationException::withMessages([
                'privacy_accepted' => 'ახალი ანგარიშის რეგისტრაციისთვის გაეცანით და დაადასტურეთ კონფიდენციალურობის პოლიტიკა.',
            ]);
        }
    }

    private function hasCurrentAccountPrivacy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return PrivacyConsent::query()
            ->where('user_id', $user->id)
            ->where('consent_type', 'account_privacy_acknowledgement')
            ->where('policy_version', PrivacyPolicy::VERSION)
            ->whereNull('withdrawn_at')
            ->exists();
    }

    private function recordAccountPrivacy(Request $request, PrivacyConsentRecorder $recorder, User $user, bool $marketing, array $metadata = []): void
    {
        $metadata = [...$metadata, 'phone' => $user->phone, 'policy_version' => PrivacyPolicy::VERSION];

        $recorder->recordForUserIfMissing(
            $request,
            $user->id,
            'account_privacy_acknowledgement',
            PrivacyPolicy::ACCOUNT_ACKNOWLEDGEMENT,
            'account_service_and_security',
            $metadata,
        );

        if ($marketing) {
            $recorder->recordForUserIfMissing(
                $request,
                $user->id,
                'marketing_updates',
                PrivacyPolicy::MARKETING_CONSENT,
                'consent',
                $metadata,
            );
        }
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
