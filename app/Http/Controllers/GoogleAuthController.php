<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PrivacyConsentRecorder;
use App\Support\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->to($this->redirectFor($request->user()));
        }

        $this->assertConfigured();

        $state = Str::random(64);
        $verifier = Str::random(96);
        $challenge = $this->base64UrlEncode(hash('sha256', $verifier, true));

        $request->session()->put([
            'google_oauth.state' => $state,
            'google_oauth.verifier' => $verifier,
            'google_oauth.started_at' => now()->timestamp,
            'google_oauth.policy_version' => PrivacyPolicy::VERSION,
        ]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'access_type' => 'online',
            'include_granted_scopes' => 'true',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request, PrivacyConsentRecorder $recorder): RedirectResponse
    {
        try {
            $this->assertConfigured();
            $this->assertValidCallback($request);

            $token = Http::asForm()
                ->acceptJson()
                ->timeout(12)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'code' => $request->string('code')->toString(),
                    'code_verifier' => $request->session()->pull('google_oauth.verifier'),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => config('services.google.redirect'),
                ])
                ->throw()
                ->json();

            $accessToken = is_array($token) ? ($token['access_token'] ?? null) : null;
            if (! is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('Google access token is missing.');
            }

            $profile = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(12)
                ->get('https://openidconnect.googleapis.com/v1/userinfo')
                ->throw()
                ->json();

            $googleId = is_array($profile) ? trim((string) ($profile['sub'] ?? '')) : '';
            $email = is_array($profile) ? Str::lower(trim((string) ($profile['email'] ?? ''))) : '';
            $verified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

            if ($googleId === '' || $email === '' || ! $verified || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Google profile is not verified.');
            }

            $user = DB::transaction(function () use ($profile, $googleId, $email): User {
                $user = User::query()->where('google_id', $googleId)->lockForUpdate()->first();

                if (! $user) {
                    $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
                }

                if ($user && $user->google_id && ! hash_equals($user->google_id, $googleId)) {
                    throw new RuntimeException('This email is already linked to another Google identity.');
                }

                $name = trim((string) ($profile['name'] ?? ''));
                if ($name === '') {
                    $name = Str::before($email, '@');
                }

                $avatar = trim((string) ($profile['picture'] ?? ''));
                $avatar = $avatar !== '' ? Str::limit($avatar, 2048, '') : null;

                if (! $user) {
                    return User::create([
                        'name' => Str::limit($name, 120, ''),
                        'phone' => null,
                        'email' => $email,
                        'google_id' => $googleId,
                        'avatar_url' => $avatar,
                        'email_verified_at' => now(),
                        'role' => 'member',
                        'status' => 'active',
                    ]);
                }

                if ($user->status !== 'active') {
                    throw new RuntimeException('This account is inactive.');
                }

                $user->forceFill([
                    'google_id' => $googleId,
                    'email' => $email,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'avatar_url' => $avatar,
                    'name' => $user->name ?: Str::limit($name, 120, ''),
                ])->save();

                return $user->refresh();
            });

            $recorder->recordForUserIfMissing(
                $request,
                $user->id,
                'account_privacy_acknowledgement',
                PrivacyPolicy::ACCOUNT_ACKNOWLEDGEMENT,
                'account_service_and_security',
                [
                    'provider' => 'google',
                    'email' => $user->email,
                    'policy_version' => $request->session()->pull('google_oauth.policy_version', PrivacyPolicy::VERSION),
                ],
            );

            Auth::login($user, true);
            $request->session()->regenerate();
            $this->clearOAuthSession($request);

            return redirect()->to($this->redirectFor($user));
        } catch (Throwable $error) {
            $this->clearOAuthSession($request);
            Log::warning('Google authentication failed', [
                'exception' => $error::class,
                'message' => $error->getMessage(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('home')->with(
                'google_auth_error',
                'Google-ით შესვლა ვერ დასრულდა. სცადეთ თავიდან.',
            );
        }
    }

    private function assertConfigured(): void
    {
        foreach (['client_id', 'client_secret', 'redirect'] as $key) {
            if (! is_string(config('services.google.'.$key)) || trim((string) config('services.google.'.$key)) === '') {
                throw new RuntimeException('Google authentication is not configured.');
            }
        }
    }

    private function assertValidCallback(Request $request): void
    {
        if ($request->filled('error')) {
            throw new RuntimeException('Google authorization was cancelled.');
        }

        $expectedState = (string) $request->session()->pull('google_oauth.state', '');
        $receivedState = $request->string('state')->toString();
        $startedAt = (int) $request->session()->pull('google_oauth.started_at', 0);

        if ($expectedState === '' || $receivedState === '' || ! hash_equals($expectedState, $receivedState)) {
            throw new RuntimeException('Google OAuth state mismatch.');
        }

        if ($startedAt === 0 || now()->timestamp - $startedAt > 600) {
            throw new RuntimeException('Google OAuth request expired.');
        }

        if (! $request->filled('code')) {
            throw new RuntimeException('Google authorization code is missing.');
        }
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

    private function clearOAuthSession(Request $request): void
    {
        $request->session()->forget([
            'google_oauth.state',
            'google_oauth.verifier',
            'google_oauth.started_at',
            'google_oauth.policy_version',
        ]);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
