<?php

namespace Tests\Feature;

use App\Models\DataSubjectRequest;
use App\Models\PrivacyConsent;
use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admission_requires_separate_privacy_and_guardian_confirmations(): void
    {
        $payload = $this->admissionPayload();

        $this->postJson('/admissions', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'privacy_accepted',
                'guardian_authority_confirmed',
                'special_category_consent',
                'privacy_policy_version',
            ]);

        $this->assertDatabaseCount('admission_applications', 0);
        $this->assertDatabaseCount('privacy_consents', 0);
    }

    public function test_admission_records_required_and_optional_consent_evidence(): void
    {
        $response = $this->postJson('/admissions', [
            ...$this->admissionPayload(),
            'privacy_accepted' => true,
            'guardian_authority_confirmed' => true,
            'special_category_consent' => true,
            'marketing_consent' => true,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertCreated();

        $applicationId = $response->json('application_id');

        foreach ([
            'admission_privacy_acknowledgement',
            'guardian_authority_confirmation',
            'child_special_category_consent',
            'marketing_updates',
        ] as $type) {
            $this->assertDatabaseHas('privacy_consents', [
                'subject_id' => $applicationId,
                'consent_type' => $type,
                'policy_version' => PrivacyPolicy::VERSION,
            ]);
        }

        $this->assertSame(4, PrivacyConsent::count());
        $this->assertNotNull(PrivacyConsent::first()->accepted_at);
        $this->assertSame(64, strlen(PrivacyConsent::first()->consent_text_hash));
    }

    public function test_demo_member_registration_requires_privacy_once_and_records_optional_marketing(): void
    {
        config([
            'services.demo_auth.enabled' => true,
            'services.demo_auth.admin_phone' => '555411831',
        ]);

        $this->postJson('/auth/demo/login', [
            'name' => 'მომხმარებელი',
            'phone' => '555200001',
        ])->assertUnprocessable()->assertJsonValidationErrors('privacy_accepted');

        $this->postJson('/auth/demo/login', [
            'name' => 'მომხმარებელი',
            'phone' => '555200001',
            'privacy_accepted' => true,
            'marketing_consent' => true,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertOk()
            ->assertJsonPath('user.role', 'member')
            ->assertJsonPath('redirect_to', route('account.status'));

        $user = User::where('phone', '+995555200001')->firstOrFail();

        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'consent_type' => 'account_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
        ]);
        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'consent_type' => 'marketing_updates',
            'legal_basis' => 'consent',
        ]);

        $before = PrivacyConsent::count();
        $this->postJson('/auth/demo/login', [
            'name' => 'მომხმარებელი',
            'phone' => '555200001',
        ])->assertOk();
        $this->assertSame($before, PrivacyConsent::count());
    }

    public function test_marketing_preference_is_changed_from_account_without_reaccepting_privacy(): void
    {
        $user = User::create([
            'name' => 'ნინო მომხმარებელი',
            'phone' => '+995555200010',
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
            'accepted_at' => now(),
        ]);

        $this->actingAs($user)->patch('/account/preferences', [
            'marketing_consent' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'consent_type' => 'marketing_updates',
            'withdrawn_at' => null,
        ]);

        $this->actingAs($user)->patch('/account/preferences', [
            'marketing_consent' => false,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertNotNull(PrivacyConsent::where('user_id', $user->id)
            ->where('consent_type', 'marketing_updates')
            ->firstOrFail()->withdrawn_at);
    }

    public function test_data_subject_request_is_registered_and_optional_consent_can_be_withdrawn(): void
    {
        $user = User::create([
            'name' => 'ნინო მშობელი',
            'phone' => '+995555200002',
            'role' => 'parent',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        PrivacyConsent::create([
            'user_id' => $user->id,
            'consent_type' => 'marketing_updates',
            'policy_version' => PrivacyPolicy::VERSION,
            'legal_basis' => 'consent',
            'consent_text_hash' => PrivacyPolicy::textHash(PrivacyPolicy::MARKETING_CONSENT),
            'accepted_at' => now(),
        ]);

        $this->actingAs($user)->post('/privacy/request', [
            'name' => $user->name,
            'phone' => $user->phone,
            'request_type' => 'withdraw_consent',
            'details' => 'გთხოვთ, შეწყდეს სარეკლამო შეტყობინებები.',
            'privacy_accepted' => '1',
        ])->assertRedirect('/privacy/request');

        $this->assertDatabaseHas('data_subject_requests', [
            'user_id' => $user->id,
            'request_type' => 'withdraw_consent',
            'status' => 'new',
        ]);
        $this->assertNotNull(PrivacyConsent::firstOrFail()->withdrawn_at);
    }

    public function test_admin_can_review_and_update_data_subject_requests(): void
    {
        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'phone' => '+995555411831',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        $dataRequest = DataSubjectRequest::create([
            'name' => 'თამარ მშობელი',
            'phone' => '+995555200003',
            'request_type' => 'access_copy',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->get('/admin/privacy')
            ->assertOk()
            ->assertSee('მონაცემთა დაცვა და მოთხოვნები')
            ->assertSee('თამარ მშობელი');

        $this->actingAs($admin)->patch('/admin/privacy/requests/'.$dataRequest->id, [
            'status' => 'completed',
            'identity_verified' => '1',
            'response_notes' => 'მონაცემების ასლი გადაეცა დადასტურებულ მომთხოვნს.',
        ])->assertRedirect();

        $dataRequest->refresh();
        $this->assertSame('completed', $dataRequest->status);
        $this->assertNotNull($dataRequest->verified_at);
        $this->assertNotNull($dataRequest->completed_at);
    }

    private function admissionPayload(): array
    {
        return [
            'parent_name' => 'ნინო ბერიძე',
            'phone' => '555123456',
            'child_name' => 'ანა ბერიძე',
            'birth_year' => 2022,
            'preferred_group' => '3-4',
            'academic_year' => '2026',
            'wants_tour' => true,
            'preferred_tour_date' => now()->addDay()->toDateString(),
            'comment' => 'ბავშვს აქვს საკვების ალერგია.',
        ];
    }
}
