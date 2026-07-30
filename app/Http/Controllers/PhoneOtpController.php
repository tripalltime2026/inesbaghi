<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if (! $isDemoAdmin) {
            $this->assertPrivacyAcceptance($request);
        }

        $user = User::firstOrNew(['phone' => $phone]);

        if ($isDemoAdmin) {
            $user->name = $user->exists ? $user->name : 'ადმინისტრატორი';
            $user->role = 'admin';
            $user->status = 'active';
        } elseif (! $user->exists || in_array($user->role, ['member', 'parent'], true)) {
            $user->name = $validated['name'];
            $user->role = 'parent';
            $user->status = 'active';
        }

        $user->phone_verified_at ??= now();
        $user->save();

        if (! $isDemoAdmin) {
            $this->recordAccountPrivacy($request, $recorder, $user, $request->boolean('marketing_consent'), ['demo' => true]);
            $this->ensureDemoFamily($user);
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
            'privacy_accepted' => ['accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
            'privacy_policy_version' => ['required', Rule::in([PrivacyPolicy::VERSION])],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
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
            'policy_version' => PrivacyPolicy::VERSION,
            'marketing_consent' => $request->boolean('marketing_consent'),
            'accepted_at' => now()->toIso8601String(),
        ]);

        Log::info('OTP requested', [
            'phone' => $phone,
            'otp_id' => $otp->id,
            'code' => app()->isLocal() ? $code : 'hidden',
        ]);

        $payload = ['request_id' => $otp->id, 'expires_in' => 300];
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

        $privacy = $request->session()->get('privacy_registration.'.$validated['request_id']);
        if (! is_array($privacy) || ($privacy['policy_version'] ?? null) !== PrivacyPolicy::VERSION) {
            throw ValidationException::withMessages(['privacy_accepted' => 'რეგისტრაციის გასაგრძელებლად საჭიროა მოქმედი კონფიდენციალურობის პირობების დადასტურება.']);
        }

        $phone = $this->normalizePhone($validated['phone']);
        $otp = OtpCode::whereKey($validated['request_id'])->where('phone', $phone)->first();

        if (! $otp || ! $otp->usable() || $otp->attempts >= config('services.sms.otp_max_attempts', 5)) {
            throw ValidationException::withMessages(['code' => 'კოდი არასწორია ან ვადა გაუვიდა.']);
        }

        $otp->increment('attempts');
        if (! Hash::check($validated['code'], $otp->code_hash)) {
            throw ValidationException::withMessages(['code' => 'კოდი არასწორია.']);
        }

        $otp->update(['consumed_at' => now()]);

        $user = User::firstOrNew(['phone' => $phone]);
        if (! $user->exists) {
            $user->name = $validated['name'];
            $user->role = 'member';
            $user->status = 'pending';
        }
        $user->phone_verified_at ??= now();
        $user->save();

        $this->recordAccountPrivacy(
            $request,
            $recorder,
            $user,
            (bool) ($privacy['marketing_consent'] ?? false),
            ['otp_request_id' => $otp->id],
        );
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
            $user->hasRole('parent') => route('parent.dashboard'),
            default => route('home'),
        };

        return response()->json([
            'user' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'demo' => $demo,
            'redirect_to' => $redirectTo,
        ]);
    }

    private function assertPrivacyAcceptance(Request $request): void
    {
        if (! $request->boolean('privacy_accepted') || $request->input('privacy_policy_version') !== PrivacyPolicy::VERSION) {
            throw ValidationException::withMessages([
                'privacy_accepted' => 'რეგისტრაციისთვის აუცილებელია კონფიდენციალურობის პოლიტიკის გაცნობა და დადასტურება.',
            ]);
        }
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

    private function ensureDemoFamily(User $user): void
    {
        if ($user->children()->exists()) {
            return;
        }

        DB::transaction(function () use ($user): void {
            if ($user->children()->exists()) {
                return;
            }

            $group = KindergartenGroup::firstOrCreate(
                ['slug' => '3-4'],
                [
                    'name' => '3-4 წელი',
                    'age_min_months' => 36,
                    'age_max_months' => 47,
                    'capacity' => 20,
                    'monthly_fee' => 600,
                    'academic_year' => '2026-2027',
                    'is_active' => true,
                ],
            );

            $child = Child::create([
                'first_name' => 'დემო',
                'last_name' => 'ბავშვი '.$user->id,
                'birth_date' => now()->subYears(4)->startOfYear(),
                'birth_year' => now()->subYears(4)->year,
            ]);

            $user->children()->attach($child->id, [
                'relationship' => 'მშობელი',
                'is_primary' => true,
                'can_pick_up' => true,
            ]);

            Enrollment::create([
                'child_id' => $child->id,
                'kindergarten_group_id' => $group->id,
                'status' => 'active',
                'starts_on' => now()->startOfMonth(),
                'enrolled_at' => now(),
            ]);
        });
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
