<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\PrivacyConsent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FamilyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_create_and_link_child_from_profile(): void
    {
        $parent = User::create([
            'name' => 'ნინო მშობელი',
            'username' => 'nino-parent',
            'password' => 'strong-password',
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->actingAs($parent)
            ->get('/account/profile')
            ->assertOk()
            ->assertSee('ბავშვის დამატება')
            ->assertSee('account/children', false);

        $this->actingAs($parent)
            ->post('/account/children', [
                'first_name' => 'ანა',
                'last_name' => 'ბერიძე',
                'birth_date' => '2022-05-10',
                'relationship' => 'დედა',
                'can_pick_up' => 1,
                'guardian_confirmation' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $child = Child::where('first_name', 'ანა')->firstOrFail();
        $this->assertDatabaseHas('child_guardians', [
            'user_id' => $parent->id,
            'child_id' => $child->id,
            'relationship' => 'დედა',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);
        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $parent->id,
            'subject_type' => Child::class,
            'subject_id' => $child->id,
            'consent_type' => 'child_profile_guardian_confirmation',
        ]);
        $this->assertFalse($parent->fresh()->canAccessParentClub());
    }

    public function test_parent_cannot_add_same_child_twice(): void
    {
        $parent = User::create([
            'name' => 'გიორგი მშობელი',
            'username' => 'giorgi-parent',
            'password' => 'strong-password',
            'role' => 'member',
            'status' => 'active',
        ]);
        $child = Child::create([
            'first_name' => 'ლუკა',
            'last_name' => 'გელაშვილი',
            'birth_date' => '2021-09-08',
            'birth_year' => 2021,
        ]);
        $parent->children()->attach($child->id, [
            'relationship' => 'მამა',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);

        $this->actingAs($parent)
            ->from('/account/profile#children')
            ->post('/account/children', [
                'first_name' => 'ლუკა',
                'last_name' => 'გელაშვილი',
                'birth_date' => '2021-09-08',
                'relationship' => 'მამა',
                'can_pick_up' => 1,
                'guardian_confirmation' => 1,
            ])
            ->assertRedirect('/account/profile')
            ->assertSessionHasErrors('first_name');

        $this->assertSame(1, Child::where('first_name', 'ლუკა')->count());
    }

    public function test_admin_can_create_parent_child_and_link_them(): void
    {
        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'username' => 'admin',
            'password' => 'admin-password',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/families/create')
            ->assertOk()
            ->assertSee('მშობლისა და ბავშვის დაკავშირება');

        $this->actingAs($admin)
            ->post('/admin/families', [
                'parent_name' => 'თამარ კობახიძე',
                'parent_username' => 'tamar-k',
                'parent_password' => 'temporary-password',
                'parent_password_confirmation' => 'temporary-password',
                'parent_phone' => '555123456',
                'child_first_name' => 'მარიამ',
                'child_last_name' => 'კობახიძე',
                'child_birth_date' => '2022-02-03',
                'relationship' => 'დედა',
                'is_primary' => 1,
                'can_pick_up' => 1,
                'authority_confirmed' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $parent = User::where('username', 'tamar-k')->firstOrFail();
        $child = Child::where('first_name', 'მარიამ')->firstOrFail();
        $this->assertSame('member', $parent->role);
        $this->assertTrue(Hash::check('temporary-password', $parent->password));
        $this->assertDatabaseHas('child_guardians', [
            'user_id' => $parent->id,
            'child_id' => $child->id,
            'relationship' => 'დედა',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'family.guardian_linked',
            'subject_id' => $child->id,
        ]);
    }

    public function test_admin_can_link_existing_parent_and_child_without_duplicate(): void
    {
        $admin = User::create(['name' => 'Admin', 'username' => 'admin2', 'password' => 'admin-password', 'role' => 'admin', 'status' => 'active']);
        $parent = User::create(['name' => 'Parent', 'username' => 'parent2', 'password' => 'parent-password', 'role' => 'member', 'status' => 'active']);
        $child = Child::create(['first_name' => 'საბა', 'birth_date' => '2021-01-02', 'birth_year' => 2021]);

        $payload = [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'relationship' => 'მამა',
            'is_primary' => 1,
            'can_pick_up' => 1,
            'authority_confirmed' => 1,
        ];

        $this->actingAs($admin)->post('/admin/families', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/families', $payload)->assertRedirect();

        $this->assertSame(1, $parent->children()->whereKey($child->id)->count());
    }

    public function test_member_cannot_access_admin_family_registration(): void
    {
        $member = User::create(['name' => 'Member', 'username' => 'member', 'password' => 'member-password', 'role' => 'member', 'status' => 'active']);

        $this->actingAs($member)
            ->get('/admin/families/create')
            ->assertForbidden();
    }
}
