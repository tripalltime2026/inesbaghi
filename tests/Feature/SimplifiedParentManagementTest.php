<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SimplifiedParentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_parent_and_set_payment_on_one_screen(): void
    {
        $admin = $this->admin('admin-simple');
        $parent = $this->parent('Parent', 'parent-simple');

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
        $parent = $this->parent('Waiting Parent', 'waiting-parent');
        $child = $this->child('ნინო', 'ტესტი');
        $this->link($parent, $child);

        $this->assertFalse($parent->isClubAccessApproved());
        $this->assertFalse($parent->canAccessParentClub());

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertRedirect(route('account.status'));
    }

    public function test_admin_screen_uses_only_children_already_linked_during_registration(): void
    {
        $admin = $this->admin('admin-child-form');
        $parent = $this->parent('Parent', 'parent-child-form');
        $child = $this->child('ანა', 'ტესტი');
        $this->link($parent, $child);

        $this->assertDatabaseCount('kindergarten_groups', 0);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('დადასტურება და ჯგუფში ჩარიცხვა')
            ->assertSee('ანა ტესტი')
            ->assertDontSee('არსებული ბავშვის არჩევა')
            ->assertDontSee('ახალი ბავშვის შექმნა')
            ->assertSee('2-3 წელი')
            ->assertSee('3-4 წელი')
            ->assertSee('4-5 წელი')
            ->assertSee('5-6 წელი');

        $this->assertDatabaseCount('kindergarten_groups', 4);
    }

    public function test_admin_verifies_linked_child_and_enrolls_in_one_action(): void
    {
        $admin = $this->admin('admin-child-link');
        $parent = $this->parent('Nino Parent', 'nino-parent');
        $child = $this->child('ანა', 'ტესტი');
        $this->link($parent, $child);
        $group = $this->group('3-4 წლის ჯგუფი', '3-4-test');

        $response = $this->actingAs($admin)->post(
            route('admin.users.children.store', $parent),
            [
                'child_id' => $child->id,
                'group_id' => $group->id,
                'starts_on' => '2026-08-03',
            ],
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
        ]);

        $parent->refresh();
        $this->assertSame('parent', $parent->role);
        $this->assertTrue($parent->isClubAccessApproved());
        $this->assertTrue($parent->canAccessParentClub());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'parent_child.verified_and_enrolled',
            'subject_id' => $child->id,
        ]);
    }

    public function test_admin_cannot_assign_another_parents_child(): void
    {
        $admin = $this->admin('admin-security');
        $firstParent = $this->parent('First Parent', 'first-parent');
        $secondParent = $this->parent('Second Parent', 'second-parent');
        $firstChild = $this->child('პირველი', 'ბავშვი');
        $secondChild = $this->child('მეორე', 'ბავშვი');
        $this->link($firstParent, $firstChild);
        $this->link($secondParent, $secondChild);
        $group = $this->group('4-5 წლის ჯგუფი', '4-5-security');

        $this->actingAs($admin)
            ->post(route('admin.users.children.store', $firstParent), [
                'child_id' => $secondChild->id,
                'group_id' => $group->id,
                'starts_on' => '2026-08-03',
            ])
            ->assertSessionHasErrors('child_id');

        $this->assertDatabaseMissing('enrollments', [
            'child_id' => $secondChild->id,
            'kindergarten_group_id' => $group->id,
        ]);
        $this->assertFalse($firstParent->fresh()->isClubAccessApproved());
    }

    public function test_group_change_preserves_old_enrollment_history(): void
    {
        $admin = $this->admin('admin-transfer');
        $parent = $this->parent('Transfer Parent', 'transfer-parent');
        $child = $this->child('ნინი', 'ტრანსფერი');
        $this->link($parent, $child);
        $oldGroup = $this->group('ძველი ჯგუფი', 'old-group');
        $newGroup = $this->group('ახალი ჯგუფი', 'new-group');

        $oldEnrollment = Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $oldGroup->id,
            'status' => 'active',
            'starts_on' => '2026-01-10',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.children.store', $parent), [
                'child_id' => $child->id,
                'group_id' => $newGroup->id,
                'starts_on' => '2026-08-10',
            ])
            ->assertSessionHasNoErrors();

        $oldEnrollment->refresh();
        $this->assertSame('completed', $oldEnrollment->status);
        $this->assertSame($oldGroup->id, $oldEnrollment->kindergarten_group_id);
        $this->assertDatabaseHas('enrollments', [
            'child_id' => $child->id,
            'kindergarten_group_id' => $newGroup->id,
            'status' => 'active',
        ]);
        $this->assertSame(2, $child->enrollments()->count());
    }

    public function test_admin_sees_login_and_can_generate_one_time_temporary_password(): void
    {
        $admin = $this->admin('admin-credentials');
        $parent = User::create([
            'name' => 'Nino Beridze',
            'username' => null,
            'password' => null,
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.credentials.reset', $parent),
        );

        $response
            ->assertRedirect()
            ->assertSessionHas('temporary_credentials');

        $credentials = session('temporary_credentials');
        $this->assertSame($parent->id, $credentials['user_id']);
        $this->assertSame('nino beridze', $credentials['username']);
        $this->assertNotSame('', $credentials['password']);

        $parent->refresh();
        $this->assertSame('nino beridze', $parent->username);
        $this->assertTrue(Hash::check($credentials['password'], $parent->password));
        $this->assertNotSame($credentials['password'], $parent->password);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.credentials_reset',
            'subject_id' => $parent->id,
        ]);
    }

    private function admin(string $username): User
    {
        return User::create([
            'name' => 'Admin',
            'username' => $username,
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function parent(string $name, string $username): User
    {
        return User::create([
            'name' => $name,
            'username' => $username,
            'password' => 'password123',
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    private function child(string $firstName, string $lastName): Child
    {
        return Child::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => '2022-05-10',
            'birth_year' => 2022,
        ]);
    }

    private function link(User $parent, Child $child): void
    {
        $parent->children()->attach($child->id, [
            'relationship' => 'parent',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);
    }

    private function group(string $name, string $slug): KindergartenGroup
    {
        return KindergartenGroup::create([
            'name' => $name,
            'slug' => $slug,
            'age_min_months' => 36,
            'age_max_months' => 60,
            'capacity' => 20,
            'monthly_fee' => 500,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }
}
