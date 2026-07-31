<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CredentialsAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->to($this->redirectFor($request->user()));
        }

        return view('auth.login');
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('account.profile');
        }

        return view('auth.register', [
            'privacyVersion' => PrivacyPolicy::VERSION,
        ]);
    }

    public function register(Request $request, PrivacyConsentRecorder $recorder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'privacy_accepted' => ['accepted'],
            'privacy_policy_version' => ['required', 'string', 'in:'.PrivacyPolicy::VERSION],
        ], [
            'name.required' => 'ჩაწერეთ სახელი ან მომხმარებლის სახელი.',
            'name.min' => 'სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'password.required' => 'შექმენით პაროლი.',
            'password.min' => 'პაროლი მინიმუმ 8 სიმბოლოს უნდა შეიცავდეს.',
            'privacy_accepted.accepted' => 'რეგისტრაციისთვის საჭიროა კონფიდენციალურობის პირობების დადასტურება.',
        ]);

        $displayName = Str::of($validated['name'])->squish()->toString();
        $username = $this->normalizeUsername($displayName);

        if (User::query()->where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'ეს სახელი უკვე გამოყენებულია. დაამატეთ გვარი ან ციფრი.',
            ]);
        }

        $user = User::create([
            'name' => $displayName,
            'username' => $username,
            'password' => Hash::make($validated['password']),
            'role' => 'member',
            'status' => 'active',
        ]);

        $recorder->recordForUserIfMissing(
            $request,
            $user->id,
            'account_privacy_acknowledgement',
            PrivacyPolicy::ACCOUNT_ACKNOWLEDGEMENT,
            'account_service_and_security',
            [
                'source' => 'password_registration',
                'policy_version' => PrivacyPolicy::VERSION,
            ],
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->route('account.profile')
            ->with('success', 'ანგარიში შეიქმნა. ახლა დაამატეთ მობილურის ნომერი და სხვა დეტალები.');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'ჩაწერეთ სახელი ან მომხმარებლის სახელი.',
            'password.required' => 'ჩაწერეთ პაროლი.',
        ]);

        $username = $this->normalizeUsername($validated['name']);
        $rateKey = 'credentials-login:'.$request->ip().':'.hash('sha256', $username);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw ValidationException::withMessages([
                'name' => "ძალიან ბევრი მცდელობაა. სცადეთ {$seconds} წამში.",
            ]);
        }

        $user = User::query()->where('username', $username)->first();
        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password) || $user->status !== 'active') {
            RateLimiter::hit($rateKey, 60);
            throw ValidationException::withMessages([
                'name' => 'სახელი ან პაროლი არასწორია.',
            ]);
        }

        RateLimiter::clear($rateKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if (! $user->phone) {
            return redirect()
                ->route('account.profile')
                ->with('success', 'შესვლა წარმატებულია. დაამატეთ მობილურის ნომერი პროფილში.');
        }

        return redirect()->to($this->redirectFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function mode(): JsonResponse
    {
        return response()->json([
            'password_auth' => true,
            'google_auth' => false,
            'phone_auth' => false,
        ]);
    }

    public function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => 'ტელეფონისა და SMS ავტორიზაცია გამორთულია.',
        ], 410);
    }

    private function normalizeUsername(string $value): string
    {
        return Str::of($value)
            ->squish()
            ->lower()
            ->toString();
    }

    private function redirectFor(User $user): string
    {
        return match (true) {
            $user->hasRole('admin') => route('admin.dashboard'),
            $user->hasRole('finance') => route('admin.payments.index'),
            $user->hasRole('teacher') => route('admin.attendance.index'),
            $user->canAccessParentClub() => route('parent.dashboard'),
            default => route('account.status'),
        };
    }
}
