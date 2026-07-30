<?php

namespace Tests\Feature;

use App\Models\PrivacyConsent;
use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_mode_exposes_temporary_demo_configuration(): void
    {
        config()->set('services.demo_auth.enabled', true);
        config()->set('services.demo_auth.admin_phone', '555411831');

        $this->getJson('/auth/mode')
            ->assertOk()
            ->assertJson([
                'demo_enabled' => true,
                'admin_phone' => '555411831',
                'privacy_policy_version' => PrivacyPolicy::VERSION,
            ]);
    }

    public function test_configured_phone_logs_in_as_active_admin_without_code(): void
    {
        config()->set('services.demo_auth.enabled', true);
        config()->set('services.demo_auth.admin_phone', '555411831');

        $response = $this->postJson('/auth/demo/login', [
            'name' => 'თორნიკე',
            'phone' => '555411831',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.status', 'active')
            ->assertJsonPath('redirect_to', route('admin.dashboard'));

        $admin = User::where('phone', '+995555411831')->firstOrFail();
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
    }

    public function test_new_mobile_creates_member_without_fake_child_or_parent_access(): void
    {
        config()->set('services.demo_auth.enabled', true);
        config()->set('services.demo_auth.admin_phone', '555411831');

        $response = $this->postJson('/auth/demo/login', [
            'name' => 'ნინო ბერიძე',
            'phone' => '555123456',
            'privacy_accepted' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'member')
            ->assertJsonPath('user.status', 'active')
            ->assertJsonPath('user.parent_club_access', false)
            ->assertJsonPath('redirect_to', route('account.status'));

        $member = User::where('phone', '+995555123456')->firstOrFail();
        $this->assertAuthenticatedAs($member);
        $this->assertSame('ნინო ბერიძე', $member->name);
        $this->assertNotNull($member->phone_verified_at);
        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $member->id,
            'consent_type' => 'account_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
        ]);
    }

    public function test_existing_registered_account_logs_in_without_reaccepting_privacy_or_marketing(): void
    {
        config()->set('services.demo_auth.enabled', true);

        $user = User::create([
            'name' => 'არსებული მომხმარებელი',
            'phone' => '+995555333444',
            'role' => 'member',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
        PrivacyConsent::create([
            'user_id' => $user->id,
            'consent_type' => 'account_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
            'legal_basis' => 'account_service_and_security',
            'consent_text_hash' => PrivacyPolicy::textHash(PrivacyPolicy::ACCOUNT_ACKNOWLEDGEMENT),
            'accepted_at' => now()->subDay(),
        ]);

        $this->postJson('/auth/demo/login', [
            'name' => 'შეცვლილი სახელი',
            'phone' => '555333444',
        ])->assertOk()
            ->assertJsonPath('user.role', 'member')
            ->assertJsonPath('redirect_to', route('account.status'));

        $this->assertSame('არსებული მომხმარებელი', $user->fresh()->name);
        $this->assertDatabaseCount('privacy_consents', 1);
    }

    public function test_existing_staff_role_is_not_downgraded_by_demo_login(): void
    {
        config()->set('services.demo_auth.enabled', true);

        $teacher = User::create([
            'name' => 'მასწავლებელი',
            'phone' => '+995555222333',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->postJson('/auth/demo/login', [
            'name' => 'სხვა სახელი',
            'phone' => '555222333',
            'privacy_accepted' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertOk()
            ->assertJsonPath('user.role', 'teacher')
            ->assertJsonPath('redirect_to', route('admin.attendance.index'));

        $this->assertSame('teacher', $teacher->fresh()->role);
        $this->assertSame('მასწავლებელი', $teacher->fresh()->name);
    }

    public function test_demo_login_is_unavailable_when_feature_is_disabled(): void
    {
        config()->set('services.demo_auth.enabled', false);

        $this->postJson('/auth/demo/login', [
            'name' => 'ნინო ბერიძე',
            'phone' => '555123456',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}
