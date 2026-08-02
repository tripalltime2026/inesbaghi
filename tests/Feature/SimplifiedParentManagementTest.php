<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimplifiedParentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_parent_and_set_payment_on_one_screen(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin-simple',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $parent = User::create([
            'name' => 'Parent',
            'username' => 'parent-simple',
            'password' => 'password123',
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.access-payment.update', $parent),
            [
                'access_approved' => '1',
                'payment_due' => '450.00',
                'payment_paid' => '100.00',
                'payment_due_at' => '2026-08-15',
                'payment_note' => 'აგვისტოს გადასახადი',
            ],
        );

        $response->assertSessionHasNoErrors();

        $parent->refresh();
        $this->assertTrue($parent->isClubAccessApproved());
        $this->assertSame(350.0, $parent->paymentOutstanding());
        $this->assertSame('აგვისტოს გადასახადი', $parent->payment_note);
    }

    public function test_registration_does_not_open_groups_or_forum_without_admin_approval(): void
    {
        $parent = User::create([
            'name' => 'Waiting Parent',
            'username' => 'waiting-parent',
            'password' => 'password123',
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->assertFalse($parent->isClubAccessApproved());
        $this->assertFalse($parent->canAccessParentClub());

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertRedirect(route('account.status'));
    }

    public function test_admin_can_create_and_link_child_without_child_registration(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin-child-link',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $parent = User::create([
            'name' => 'Nino Parent',
            'username' => 'nino-parent',
            'password' => 'password123',
            'role' => 'member',
            'status' => 'active',
        ]);

        $group = KindergartenGroup::create([
            'name' => '3-4 წლის ჯგუფი',
            'slug' => '3-4-test',
            'age_min_months' => 36,
            'age_max_months' => 48,
            'capacity' => 20,
            'monthly_fee' => 500,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.users.children.store', $parent),
            [
                'first_name' => 'ანა',
                'last_name' => 'ტესტი',
                'birth_date' => '2022-05-10',
                'group_id' => $group->id,
                'enrollment_status' => 'active',
                'starts_on' => '2026-08-03',
            ],
        );

        $response->assertSessionHasNoErrors();

        $child = Child::query()->where('first_name', 'ანა')->firstOrFail();
        $this->assertTrue($parent->children()->whereKey($child->id)->exists());
        $this->assertDatabaseHas('enrollments', [
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
        ]);

        $parent->refresh();
        $this->assertSame('parent', $parent->role);

        $this->actingAs($parent)
            ->get(route('account.status'))
            ->assertOk()
            ->assertSee('ანა')
            ->assertSee('3-4 წლის ჯგუფი');
    }
}
