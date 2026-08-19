<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentChildLinkingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_profile_makes_child_linking_required_when_child_is_missing(): void
    {
        $parent = $this->parent('missing-child-parent');

        $this->actingAs($parent)
            ->get(route('account.profile'))
            ->assertOk()
            ->assertSee('ბავშვის მიბმა აუცილებელია');

        $this->actingAs($parent)
            ->patch(route('account.profile.update'), [
                'username' => $parent->username,
                'name' => $parent->name,
                'phone' => $parent->phone,
                'email' => $parent->email,
            ])
            ->assertSessionHasErrors('child_first_name');
    }

    public function test_parent_can_link_missing_child_while_saving_profile(): void
    {
        $parent = $this->parent('parent-links-child');

        $response = $this->actingAs($parent)
            ->patch(route('account.profile.update'), [
                'username' => $parent->username,
                'name' => $parent->name,
                'phone' => $parent->phone,
                'email' => $parent->email,
                'child_first_name' => 'ანა',
                'child_last_name' => 'ტესტი',
                'child_birth_date' => '2022-05-10',
            ]);

        $response
            ->assertRedirect(route('account.status'))
            ->assertSessionHasNoErrors();

        $child = Child::query()->where('first_name', 'ანა')->where('last_name', 'ტესტი')->firstOrFail();

        $this->assertDatabaseHas('child_guardians', [
            'user_id' => $parent->id,
            'child_id' => $child->id,
            'is_primary' => true,
        ]);
        $this->assertTrue($parent->fresh()->hasLinkedChild());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parent_child.linked_by_parent',
            'subject_id' => $child->id,
        ]);
    }

    public function test_admin_can_link_missing_child_and_enroll_in_one_action(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'username' => 'admin-child-flow',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $parent = $this->parent('admin-links-child');
        $group = KindergartenGroup::query()->create([
            'name' => '3-4 წლის ჯგუფი',
            'slug' => 'admin-link-flow',
            'age_min_months' => 36,
            'age_max_months' => 48,
            'capacity' => 20,
            'monthly_fee' => 500,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.children.store', $parent), [
                'child_first_name' => 'ნინი',
                'child_last_name' => 'ბერიძე',
                'child_birth_date' => '2022-06-12',
                'group_id' => $group->id,
                'enroll_now' => '1',
            ]);

        $response->assertSessionHasNoErrors();

        $child = Child::query()->where('first_name', 'ნინი')->where('last_name', 'ბერიძე')->firstOrFail();

        $this->assertDatabaseHas('child_guardians', [
            'user_id' => $parent->id,
            'child_id' => $child->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => now()->toDateString(),
        ]);

        $parent->refresh();
        $this->assertSame('parent', $parent->role);
        $this->assertTrue($parent->isClubAccessApproved());
        $this->assertTrue($parent->canAccessParentClub());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parent_child.linked_by_admin',
            'subject_id' => $child->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parent_child.verified_and_enrolled',
            'subject_id' => $child->id,
        ]);
    }

    private function parent(string $username): User
    {
        return User::query()->create([
            'name' => 'Parent Test',
            'username' => $username,
            'password' => 'password123',
            'phone' => '+9955'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => $username.'@example.com',
            'role' => 'member',
            'status' => 'active',
        ]);
    }
}
