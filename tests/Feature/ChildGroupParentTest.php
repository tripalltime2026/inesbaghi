<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildGroupParentTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_dashboard_only_contains_own_children(): void
    {
        $parent = $this->parent('+995555100001', 'ნინო');
        $otherParent = $this->parent('+995555100002', 'მარიამი');
        $group = $this->group(10);

        $ownChild = Child::create(['first_name' => 'ანა', 'last_name' => 'ბერიძე', 'birth_year' => 2022]);
        $otherChild = Child::create(['first_name' => 'ლუკა', 'last_name' => 'სხვისი', 'birth_year' => 2021]);
        $ownChild->guardians()->attach($parent->id, ['relationship' => 'parent', 'is_primary' => true, 'can_pick_up' => true]);
        $otherChild->guardians()->attach($otherParent->id, ['relationship' => 'parent', 'is_primary' => true, 'can_pick_up' => true]);
        Enrollment::create(['child_id' => $ownChild->id, 'kindergarten_group_id' => $group->id, 'status' => 'active', 'starts_on' => '2026-09-01']);
        Enrollment::create(['child_id' => $otherChild->id, 'kindergarten_group_id' => $group->id, 'status' => 'active', 'starts_on' => '2026-09-01']);

        $this->actingAs($parent)
            ->get('/parent')
            ->assertOk()
            ->assertSee('ანა')
            ->assertDontSee('ლუკა')
            ->assertDontSee('სხვისი');
    }

    public function test_group_capacity_blocks_second_activation(): void
    {
        $admin = $this->admin();
        $group = $this->group(1);
        $first = Child::create(['first_name' => 'პირველი', 'birth_year' => 2021]);
        $second = Child::create(['first_name' => 'მეორე', 'birth_year' => 2021]);
        Enrollment::create(['child_id' => $first->id, 'kindergarten_group_id' => $group->id, 'status' => 'active', 'starts_on' => '2026-09-01']);
        $pending = Enrollment::create(['child_id' => $second->id, 'kindergarten_group_id' => $group->id, 'status' => 'pending', 'starts_on' => '2026-09-01']);

        $this->actingAs($admin)
            ->from("/admin/children/{$second->id}")
            ->patch("/admin/enrollments/{$pending->id}", [
                'status' => 'active',
                'starts_on' => '2026-09-01',
                'ends_on' => null,
            ])
            ->assertRedirect("/admin/children/{$second->id}")
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('enrollments', ['id' => $pending->id, 'status' => 'pending']);
    }

    public function test_admin_can_activate_when_group_has_space(): void
    {
        $admin = $this->admin();
        $group = $this->group(2);
        $child = Child::create(['first_name' => 'ანა', 'birth_year' => 2022]);
        $enrollment = Enrollment::create(['child_id' => $child->id, 'kindergarten_group_id' => $group->id, 'status' => 'pending', 'starts_on' => '2026-09-01']);

        $this->actingAs($admin)
            ->patch("/admin/enrollments/{$enrollment->id}", [
                'status' => 'active',
                'starts_on' => '2026-09-01',
                'ends_on' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'active']);
        $this->assertNotNull($enrollment->fresh()->enrolled_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enrollment.updated', 'subject_id' => $enrollment->id]);
    }

    public function test_group_capacity_cannot_drop_below_active_count(): void
    {
        $admin = $this->admin();
        $group = $this->group(3);
        foreach (['ანა', 'ლუკა'] as $name) {
            $child = Child::create(['first_name' => $name, 'birth_year' => 2021]);
            Enrollment::create(['child_id' => $child->id, 'kindergarten_group_id' => $group->id, 'status' => 'active', 'starts_on' => '2026-09-01']);
        }

        $this->actingAs($admin)
            ->from("/admin/groups/{$group->id}")
            ->patch("/admin/groups/{$group->id}", [
                'name' => $group->name,
                'capacity' => 1,
                'monthly_fee' => 600,
                'academic_year' => '2026-2027',
                'is_active' => true,
            ])
            ->assertRedirect("/admin/groups/{$group->id}")
            ->assertSessionHasErrors('capacity');

        $this->assertSame(3, $group->fresh()->capacity);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'ადმინისტრატორი',
            'phone' => '+995555100099',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }

    private function parent(string $phone, string $name): User
    {
        return User::create([
            'name' => $name,
            'phone' => $phone,
            'role' => 'parent',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }

    private function group(int $capacity): KindergartenGroup
    {
        return KindergartenGroup::create([
            'name' => '3-4 წელი',
            'slug' => '3-4-'.uniqid(),
            'age_min_months' => 36,
            'age_max_months' => 47,
            'capacity' => $capacity,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }
}
