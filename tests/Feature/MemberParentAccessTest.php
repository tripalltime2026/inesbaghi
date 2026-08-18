<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\PrivacyConsent;
use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberParentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_enrollment_and_admin_approval_unlock_parent_club(): void
    {
        $user = $this->user('რეგისტრირებული მშობელი', '555310001', 'member');
        $group = $this->group();
        $child = Child::create(['first_name' => 'ანა', 'last_name' => 'ბერიძე', 'birth_year' => 2022]);
        $user->children()->attach($child->id, [
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);
        $enrollment = Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'pending',
            'starts_on' => now()->startOfMonth(),
        ]);

        $this->actingAs($user)->get('/parent')->assertRedirect(route('account.status'));
        $this->actingAs($user)->get('/account')
            ->assertOk()
            ->assertSee('ბავშვი მიბმულია')
            ->assertSee('დადასტურებას ელოდება');

        $enrollment->update(['status' => 'active', 'enrolled_at' => now()]);

        $this->actingAs($user)->get('/parent')->assertRedirect(route('account.status'));

        $user->update(['club_access_approved_at' => now()]);

        $this->actingAs($user)->get('/parent')
            ->assertOk()
            ->assertSee('მშობელთა კლუბი');
    }

    public function test_phone_verification_is_not_required_but_inactive_account_cannot_enter_parent_club(): void
    {
        $user = $this->user('დადასტურებული მშობელი', '555310002', 'parent');
        $user->update(['phone_verified_at' => null]);
        $group = $this->group('4-5');
        $child = Child::create(['first_name' => 'ლუკა', 'last_name' => 'ონიანი', 'birth_year' => 2021]);
        $user->children()->attach($child->id, ['relationship' => 'მშობელი', 'is_primary' => true, 'can_pick_up' => true]);
        Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => now()->startOfMonth(),
            'enrolled_at' => now(),
        ]);

        $this->actingAs($user)->get('/parent')->assertOk();

        $user->update(['status' => 'suspended']);
        $this->actingAs($user)->getJson('/parent/forum/data')
            ->assertForbidden()
            ->assertJsonPath('account_status_url', route('account.status'));
    }

    public function test_admin_registry_distinguishes_pending_approved_and_applicant_accounts(): void
    {
        $admin = $this->user('ადმინისტრატორი', '555411831', 'admin');
        $registered = $this->user('მხოლოდ ანგარიში', '555310010');
        $applicant = $this->user('განაცხადის ავტორი', '555310011');
        AdmissionApplication::create([
            'parent_name' => $applicant->name,
            'phone' => $applicant->phone,
            'child_name' => 'საბა',
            'preferred_group' => '3-4',
            'academic_year' => '2026',
            'wants_tour' => false,
            'status' => 'new',
        ]);

        $activeParent = $this->user('აქტიური მშობელი', '555310012', 'parent');
        $group = $this->group('5-6');
        $child = Child::create(['first_name' => 'ნია', 'last_name' => 'გიორგაძე', 'birth_year' => 2020]);
        $activeParent->children()->attach($child->id, ['relationship' => 'მშობელი', 'is_primary' => true, 'can_pick_up' => true]);
        Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => now()->startOfMonth(),
            'enrolled_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('მშობლები და ბავშვები')
            ->assertSee($registered->name)
            ->assertSee($applicant->name)
            ->assertSee($activeParent->name)
            ->assertSee('მშობელი დადასტურებულია');

        $this->actingAs($admin)->get('/admin/users?access=approved')
            ->assertOk()
            ->assertSee($activeParent->name)
            ->assertDontSee($registered->name);
    }

    private function user(string $name, string $phone, string $role = 'member'): User
    {
        $user = User::create([
            'name' => $name,
            'phone' => str_starts_with($phone, '+995') ? $phone : '+995'.$phone,
            'role' => $role,
            'status' => 'active',
            'phone_verified_at' => now(),
            'club_access_approved_at' => $role === 'parent' ? now() : null,
        ]);

        PrivacyConsent::create([
            'user_id' => $user->id,
            'consent_type' => 'account_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
            'legal_basis' => 'account_service_and_security',
            'consent_text_hash' => PrivacyPolicy::textHash(PrivacyPolicy::ACCOUNT_ACKNOWLEDGEMENT),
            'accepted_at' => now(),
        ]);

        return $user;
    }

    private function group(string $slug = '3-4'): KindergartenGroup
    {
        return KindergartenGroup::create([
            'name' => $slug.' წელი',
            'slug' => $slug,
            'age_min_months' => 36,
            'age_max_months' => 71,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }
}
