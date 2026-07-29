<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PhoneOtpController extends Controller
{
    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^(?:\+?995)?5\d{8}$/'],
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

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

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

        Auth::login($user, true);
        $request->session()->regenerate();

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
            'redirect_to' => $redirectTo,
        ]);
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

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '995')) {
            $digits = substr($digits, 3);
        }

        return '+995'.$digits;
    }
}
