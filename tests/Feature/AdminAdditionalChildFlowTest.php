<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdditionalChildFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_second_child_to_existing_parent_and_assign_group_immediately(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin-additional-child',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $parent = User::create([
            'name' => 'Parent',
            'username' => 'parent-with-child',
            'password' => 'password123',
            'phone' => '+995555330001',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $firstGroup = $this->group('3-4 წელი', 'first-child-group');
        $secondGroup = $this->group('5-6 წელი', 'second-child-group');

        $firstChild = Child::create([
            'first_name' => 'ანა',
            'last_name' => 'ბერიძე',
            'birth_date' => '2021-04-10',
            'birth_year' => 2021,
        ]);
        $parent->children()->attach($firstChild->id, [
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);
        Enrollment::create([
            'child_id' => $firstChild->id,
            'kindergarten_group_id' => $firstGroup->id,
            'status' => 'active',
            'starts_on' => '2026-01-10',
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.children.store', $parent), [
            'child_first_name' => 'ნიკა',
            'child_last_name' => 'ბერიძე',
            'child_birth_date' => '2022-06-12',
            'group_id' => $secondGroup->id,
            'enroll_now' => '1',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $secondChild = Child::query()
            ->where('first_name', 'ნიკა')
            ->where('last_name', 'ბერიძე')
            ->firstOrFail();

        $this->assertSame(2, $parent->fresh()->children()->count());
        $this->assertDatabaseHas('child_guardians', [
            'user_id' => $parent->id,
            'child_id' => $secondChild->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'child_id' => $secondChild->id,
            'kindergarten_group_id' => $secondGroup->id,
            'status' => 'active',
            'starts_on' => now()->startOfDay()->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('enrollments', [
            'child_id' => $firstChild->id,
            'kindergarten_group_id' => $firstGroup->id,
            'status' => 'active',
        ]);

        $parent->refresh();
        $this->assertTrue($parent->isClubAccessApproved());
        $this->assertTrue($parent->canAccessParentClub());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parent_child.linked_by_admin',
            'subject_id' => $secondChild->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parent_child.verified_and_enrolled',
            'subject_id' => $secondChild->id,
        ]);
    }

    private function group(string $name, string $slug): KindergartenGroup
    {
        return KindergartenGroup::create([
            'name' => $name,
            'slug' => $slug,
            'age_min_months' => 36,
            'age_max_months' => 72,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }
}
