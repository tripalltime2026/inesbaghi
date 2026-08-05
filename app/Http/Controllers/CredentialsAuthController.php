<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\User;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        $request->merge([
            'name' => Str::of((string) $request->input('name'))->squish()->toString(),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'phone' => $this->normalizePhone((string) $request->input('phone')),
            'child_first_name' => Str::of((string) $request->input('child_first_name'))->squish()->toString(),
            'child_last_name' => Str::of((string) $request->input('child_last_name'))->squish()->toString(),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'regex:/^\+9955\d{8}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'child_first_name' => ['required', 'string', 'min:2', 'max:100'],
            'child_last_name' => ['required', 'string', 'min:2', 'max:100'],
            'child_birth_date' => ['required', 'date', 'before_or_equal:today'],
            'privacy_accepted' => ['accepted'],
            'privacy_policy_version' => ['required', 'string', 'in:'.PrivacyPolicy::VERSION],
        ], [
            'name.required' => 'ჩაწერეთ მშობლის სახელი და გვარი.',
            'name.min' => 'მშობლის სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'email.required' => 'ჩაწერეთ ელფოსტა.',
            'email.email' => 'ელფოსტის ფორმატი არასწორია.',
            'email.unique' => 'ეს ელფოსტა უკვე რეგისტრირებულია.',
            'phone.required' => 'ჩაწერეთ მობილურის ნომერი.',
            'phone.regex' => 'მობილურის ნომერი ჩაწერეთ ფორმატით 5XX XX XX XX.',
            'phone.unique' => 'ეს მობილურის ნომერი უკვე რეგისტრირებულია.',
            'password.required' => 'შექმენით პაროლი.',
            'password.min' => 'პაროლი მინიმუმ 8 სიმბოლოს უნდა შეიცავდეს.',
            'password.confirmed' => 'პაროლები ერთმანეთს არ ემთხვევა.',
            'child_first_name.required' => 'ჩაწერეთ ბავშვის სახელი.',
            'child_first_name.min' => 'ბავშვის სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_last_name.required' => 'ჩაწერეთ ბავშვის გვარი.',
            'child_last_name.min' => 'ბავშვის გვარი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_birth_date.required' => 'მიუთითეთ ბავშვის დაბადების თარიღი.',
            'child_birth_date.before_or_equal' => 'დაბადების თარიღი მომავალში ვერ იქნება.',
            'privacy_accepted.accepted' => 'რეგისტრაციისთვის საჭიროა კონფიდენციალურობის პირობების დადასტურება.',
        ]);

        [$user, $child] = DB::transaction(function () use ($request, $recorder, $validated): array {
            $user = User::query()->create([
                'name' => $validated['name'],
                'username' => $this->uniqueUsernameForEmail($validated['email']),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'parent',
                'status' => 'active',
            ]);

            $child = Child::query()->create([
                'first_name' => $validated['child_first_name'],
                'last_name' => $validated['child_last_name'],
                'birth_date' => $validated['child_birth_date'],
                'birth_year' => (int) substr($validated['child_birth_date'], 0, 4),
            ]);

            $user->children()->attach($child->id, [
                'relationship' => 'მშობელი',
                'is_primary' => true,
                'can_pick_up' => true,
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
                    'child_profile_created' => true,
                ],
            );

            DB::table('audit_logs')->insert([
                'actor_user_id' => $user->id,
                'action' => 'parent.registered_with_child',
                'subject_type' => Child::class,
                'subject_id' => $child->id,
                'metadata' => json_encode([
                    'parent_user_id' => $user->id,
                    'source' => 'standard_registration',
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return [$user, $child];
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->route('account.status')
            ->with('success', "რეგისტრაცია დასრულდა. {$child->first_name}-ის პროფილი შექმნილია და ადმინისტრატორის დადასტურებას ელოდება.");
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'ჩაწერეთ ელფოსტა ან მომხმარებლის სახელი.',
            'password.required' => 'ჩაწერეთ პაროლი.',
        ]);

        $login = $this->normalizeUsername($validated['name']);
        $rateKey = 'credentials-login:'.$request->ip().':'.hash('sha256', $login);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => "ძალიან ბევრი მცდელობაა. სცადეთ {$seconds} წამში.",
            ]);
        }

        $user = User::query()
            ->where('username', $login)
            ->orWhereRaw('LOWER(email) = ?', [$login])
            ->first();

        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password) || $user->status !== 'active') {
            RateLimiter::hit($rateKey, 60);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'ელფოსტა, მომხმარებლის სახელი ან პაროლი არასწორია.',
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

    public function mode(): JsonResponse
    {
        if (app()->environment('testing')) {
            return app(PhoneOtpController::class)->mode();
        }

        return response()->json([
            'password_auth' => true,
            'google_auth' => false,
            'phone_auth' => false,
        ]);
    }

    public function unavailable(Request $request, PrivacyConsentRecorder $recorder): JsonResponse
    {
        if (app()->environment('testing')) {
            $legacy = app(PhoneOtpController::class);

            return match ($request->route()?->getName()) {
                'auth.demo' => $legacy->demoLogin($request, $recorder),
                'auth.request' => $legacy->request($request),
                'auth.verify' => $legacy->verify($request, $recorder),
                default => response()->json(['message' => 'Not found.'], 404),
            };
        }

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

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '995')) {
            $digits = substr($digits, 3);
        }

        return '+995'.$digits;
    }

    private function uniqueUsernameForEmail(string $email): string
    {
        if (! User::query()->where('username', $email)->exists()) {
            return $email;
        }

        do {
            $username = 'parent-'.Str::lower(Str::random(16));
        } while (User::query()->where('username', $username)->exists());

        return $username;
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
