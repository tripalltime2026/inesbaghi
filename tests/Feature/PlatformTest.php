<?php

namespace Tests\Feature;

use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_is_persisted(): void
    {
        $this->postJson('/admissions', [
            'parent_name' => 'ნინო ბერიძე',
            'phone' => '555123456',
            'child_name' => 'ანა',
            'birth_year' => 2022,
            'preferred_group' => '3-4',
            'academic_year' => '2026',
            'wants_tour' => false,
            'privacy_accepted' => true,
            'guardian_authority_confirmed' => true,
            'special_category_consent' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertCreated();

        $this->assertDatabaseHas('admission_applications', ['phone' => '+995555123456']);
        $this->assertDatabaseHas('privacy_consents', [
            'consent_type' => 'admission_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
        ]);
    }

    public function test_phone_registration_cannot_become_admin_from_client(): void
    {
        $request = $this->postJson('/auth/phone/request', [
            'name' => 'თორნიკე',
            'phone' => '555411831',
            'privacy_accepted' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertOk();

        $this->postJson('/auth/phone/verify', [
            'request_id' => $request->json('request_id'),
            'name' => 'თორნიკე',
            'phone' => '555411831',
            'code' => $request->json('debug_code'),
        ])->assertOk()
            ->assertJsonPath('user.role', 'member')
            ->assertJsonPath('user.status', 'active')
            ->assertJsonPath('redirect_to', route('account.status'));

        $this->assertDatabaseHas('users', [
            'phone' => '+995555411831',
            'role' => 'member',
            'status' => 'active',
        ]);
    }
}
