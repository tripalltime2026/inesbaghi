<?php

namespace Tests\Feature;

use App\Models\PrivacyConsent;
use App\Models\User;
use App\Services\MailchimpMarketing;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailchimpMarketingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.mailchimp', [
            'enabled' => true,
            'api_key' => 'test-key-us11',
            'server_prefix' => 'us11',
            'audience_id' => 'b32ed8e812',
            'timeout_seconds' => 5,
        ]);
    }

    public function test_registration_with_marketing_consent_requests_double_opt_in(): void
    {
        Http::fake([
            'https://us11.api.mailchimp.com/3.0/*' => Http::response(['id' => 'member-id'], 200),
        ]);

        $this->post('/registratsia', [
            'name' => 'თამარ კიკნაძე',
            'email' => 'parent@example.com',
            'phone' => '555123456',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'child_first_name' => 'ანა',
            'child_last_name' => 'კიკნაძე',
            'child_birth_date' => '2022-05-14',
            'marketing_consent' => '1',
            'privacy_accepted' => '1',
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertRedirect(route('account.status'));

        $user = User::where('email', 'parent@example.com')->firstOrFail();

        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'consent_type' => 'marketing_updates',
            'legal_basis' => 'consent',
        ]);

        Http::assertSent(function ($request): bool {
            if ($request->method() !== 'PUT') {
                return false;
            }

            return $request->url() === 'https://us11.api.mailchimp.com/3.0/lists/b32ed8e812/members/'.md5('parent@example.com')
                && $request['email_address'] === 'parent@example.com'
                && $request['status'] === 'pending'
                && $request['language'] === 'ka';
        });

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && str_ends_with($request->url(), '/tags')
                && collect($request['tags'])->contains(fn (array $tag): bool => $tag['name'] === 'Parent');
        });
    }

    public function test_registration_without_marketing_consent_does_not_call_mailchimp(): void
    {
        Http::fake();

        $this->post('/registratsia', [
            'name' => 'თამარ კიკნაძე',
            'email' => 'parent@example.com',
            'phone' => '555123456',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'child_first_name' => 'ანა',
            'child_last_name' => 'კიკნაძე',
            'child_birth_date' => '2022-05-14',
            'privacy_accepted' => '1',
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertRedirect(route('account.status'));

        $this->assertDatabaseMissing('privacy_consents', [
            'consent_type' => 'marketing_updates',
        ]);
        Http::assertNothingSent();
    }

    public function test_disabling_preference_withdraws_consent_and_unsubscribes_member(): void
    {
        Http::fake([
            'https://us11.api.mailchimp.com/3.0/*' => Http::response(['id' => 'member-id'], 200),
        ]);

        $user = User::create([
            'name' => 'თამარ კიკნაძე',
            'username' => 'parent@example.com',
            'email' => 'parent@example.com',
            'phone' => '+995555123456',
            'password' => 'StrongPass123',
            'role' => 'parent',
            'status' => 'active',
        ]);

        PrivacyConsent::create([
            'user_id' => $user->id,
            'consent_type' => 'marketing_updates',
            'policy_version' => PrivacyPolicy::VERSION,
            'legal_basis' => 'consent',
            'consent_text_hash' => PrivacyPolicy::textHash(PrivacyPolicy::MARKETING_CONSENT),
            'accepted_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('account.preferences.update'), [
            'marketing_consent' => '0',
        ])->assertRedirect();

        $this->assertNotNull(PrivacyConsent::firstOrFail()->withdrawn_at);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'PATCH'
                && $request->url() === 'https://us11.api.mailchimp.com/3.0/lists/b32ed8e812/members/'.md5('parent@example.com')
                && $request['status'] === 'unsubscribed';
        });
    }

    public function test_mailchimp_failure_never_blocks_registration(): void
    {
        Http::fake([
            'https://us11.api.mailchimp.com/3.0/*' => Http::response([
                'title' => 'Service Unavailable',
                'detail' => 'Temporary failure',
            ], 503),
        ]);

        $response = $this->post('/registratsia', [
            'name' => 'თამარ კიკნაძე',
            'email' => 'parent@example.com',
            'phone' => '555123456',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'child_first_name' => 'ანა',
            'child_last_name' => 'კიკნაძე',
            'child_birth_date' => '2022-05-14',
            'marketing_consent' => '1',
            'privacy_accepted' => '1',
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ]);

        $response->assertRedirect(route('account.status'));
        $this->assertDatabaseHas('users', ['email' => 'parent@example.com']);
        $this->assertDatabaseHas('privacy_consents', ['consent_type' => 'marketing_updates']);
    }

    public function test_service_stays_disabled_without_secret_configuration(): void
    {
        config()->set('services.mailchimp.api_key', null);

        Http::fake();

        $user = User::make([
            'name' => 'თამარ კიკნაძე',
            'email' => 'parent@example.com',
        ]);

        $this->assertFalse(app(MailchimpMarketing::class)->requestDoubleOptIn($user));
        Http::assertNothingSent();
    }
}
