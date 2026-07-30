<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\KindergartenGroup;
use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_inquiry_form_does_not_request_child_name(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('შეავსეთ ჩარიცხვის განაცხადი ან დაგეგმეთ გაცნობითი ვიზიტი')
            ->assertDontSee('name="child_name"', false)
            ->assertDontSee('ბავშვის სახელი და გვარი *');
    }

    public function test_inquiry_is_saved_without_child_name(): void
    {
        $this->postJson('/admissions', [
            'parent_name' => 'ნინო ბერიძე',
            'phone' => '555123456',
            'birth_year' => 2022,
            'preferred_group' => '3-4',
            'academic_year' => '2026',
            'wants_tour' => true,
            'privacy_accepted' => true,
            'guardian_authority_confirmed' => true,
            'special_category_consent' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertCreated();

        $this->assertDatabaseHas('admission_applications', [
            'phone' => '+995555123456',
            'child_name' => null,
            'status' => 'new',
        ]);
    }

    public function test_admin_completes_child_name_before_conversion(): void
    {
        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'phone' => '+995555411831',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        KindergartenGroup::create([
            'name' => '3-4 წელი',
            'slug' => '3-4',
            'age_min_months' => 36,
            'age_max_months' => 47,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        $application = AdmissionApplication::create([
            'parent_name' => 'ნინო ბერიძე',
            'phone' => '+995555123456',
            'child_name' => null,
            'birth_year' => 2022,
            'preferred_group' => '3-4',
            'academic_year' => '2026',
            'wants_tour' => true,
            'status' => 'new',
            'source' => 'website',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.admissions.show', $application))
            ->post(route('admin.admissions.convert', $application))
            ->assertRedirect(route('admin.admissions.show', $application))
            ->assertSessionHasErrors('child_name');

        $this->assertDatabaseCount('children', 0);

        $this->actingAs($admin)->patch(route('admin.admissions.update', $application), [
            'child_name' => 'ანა ბერიძე',
            'birth_year' => 2022,
            'status' => 'new',
        ])->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.admissions.convert', $application))
            ->assertRedirect();

        $this->assertDatabaseHas('children', [
            'first_name' => 'ანა',
            'last_name' => 'ბერიძე',
            'birth_year' => 2022,
        ]);
        $this->assertDatabaseHas('enrollments', ['status' => 'pending']);
    }
}
