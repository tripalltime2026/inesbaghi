<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.google.client_id', 'test-client');
        config()->set('services.google.client_secret', 'test-credential');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');
    }

    public function test_public_login_interface_contains_only_google(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Google-ით გაგრძელება')
            ->assertSee('/css/google-auth.css', false)
            ->assertSee('/js/google-auth.js', false)
            ->assertDontSee('შესვლა ტელეფონით')
            ->assertDontSee('otpRequest', false);
    }

    public function test_google_redirect_uses_state_and_pkce(): void
    {
        $response = $this->get('/auth/google');
        $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
        $response->assertSessionHas('google_oauth.state');
        $response->assertSessionHas('google_oauth.verifier');

        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('scope=openid%20email%20profile', $location);
        $this->assertStringContainsString('code_challenge_method=S256', $location);
    }

    public function test_verified_google_profile_creates_member_without_phone(): void
    {
        $this->get('/auth/google')->assertRedirect();
        $state = (string) session('google_oauth.state');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'parent@example.com',
                'email_verified' => true,
                'name' => 'ნინო ბერიძე',
            ], 200),
        ]);

        $this->get('/auth/google/callback?code=authorization-code&state='.urlencode($state))
            ->assertRedirect(route('account.status'));

        $user = User::where('google_id', 'google-user-123')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('member', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertNull($user->phone);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->canAccessParentClub());
        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'consent_type' => 'account_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
        ]);
    }

    public function test_existing_admin_keeps_role_when_google_is_linked_by_email(): void
    {
        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'phone' => '+995555000099',
            'email' => 'owner@example.com',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        $this->get('/auth/google')->assertRedirect();
        $state = (string) session('google_oauth.state');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'admin-token'], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-admin-456',
                'email' => 'owner@example.com',
                'email_verified' => true,
                'name' => 'Google Name',
            ], 200),
        ]);

        $this->get('/auth/google/callback?code=admin-code&state='.urlencode($state))
            ->assertRedirect(route('admin.dashboard'));

        $admin->refresh();
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('google-admin-456', $admin->google_id);
        $this->assertSame('ადმინისტრატორი', $admin->name);
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $this->get('/auth/google')->assertRedirect();
        Http::fake();

        $this->get('/auth/google/callback?code=bad-code&state=wrong-state')
            ->assertRedirect(route('home'))
            ->assertSessionHas('google_auth_error');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        Http::assertNothingSent();
    }
}
