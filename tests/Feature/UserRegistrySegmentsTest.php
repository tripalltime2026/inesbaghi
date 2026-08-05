<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegistrySegmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_clear_user_databases_and_counts(): void
    {
        $admin = $this->admin();
        $group = $this->group();

        $this->clubParent('კლუბის მშობელი', '+995555410001', $group);
        $this->incompleteParent('არასრული მშობელი', '+995555410002');
        $this->cancelledParent('გაუქმებული მშობელი', '+995555410003');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('მომხმარებელთა ბაზა')
            ->assertSee('დადასტურებას ელოდება')
            ->assertSee('კლუბის წევრები')
            ->assertSee('დამტკიცებული, მაგრამ არასრული')
            ->assertSee('გაუქმებული')
            ->assertSee('კლუბის მშობელი')
            ->assertSee('არასრული მშობელი')
            ->assertSee('გაუქმებული მშობელი')
            ->assertSee('user-registry.css');
    }

    public function test_club_member_segment_is_strictly_filtered(): void
    {
        $admin = $this->admin();
        $group = $this->group();

        $this->clubParent('წვდომიანი მშობელი', '+995555410011', $group);
        $this->incompleteParent('ფილტრიდან გამოსარიცხი მშობელი', '+995555410012');
        $this->cancelledParent('გაუქმებული ფილტრიდან გამოსარიცხი', '+995555410013');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['segment' => 'club_active']))
            ->assertOk()
            ->assertSee('წვდომიანი მშობელი')
            ->assertDontSee('ფილტრიდან გამოსარიცხი მშობელი')
            ->assertDontSee('გაუქმებული ფილტრიდან გამოსარიცხი');
    }

    public function test_admin_can_cancel_account_and_club_access_is_blocked(): void
    {
        $admin = $this->admin();
        $group = $this->group();
        $parent = $this->clubParent('გასაუქმებელი მშობელი', '+995555410021', $group);

        $this->assertTrue($parent->canAccessParentClub());

        $this->actingAs($admin)
            ->patch(route('admin.users.access-payment.update', $parent), [
                'account_status' => 'cancelled',
                'access_approved' => '1',
                'payment_due' => '600',
                'payment_paid' => '200',
                'payment_due_at' => now()->addWeek()->format('Y-m-d'),
                'payment_note' => 'ანგარიში მშობლის მოთხოვნით გაუქმდა.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $parent->refresh();

        $this->assertSame('cancelled', $parent->status);
        $this->assertFalse($parent->canAccessParentClub());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.registry_status_updated',
            'subject_id' => $parent->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['segment' => 'cancelled']))
            ->assertOk()
            ->assertSee('გასაუქმებელი მშობელი');
    }

    public function test_approved_incomplete_segment_shows_parent_missing_active_group(): void
    {
        $admin = $this->admin();
        $parent = User::create([
            'name' => 'ჯგუფის გარეშე მშობელი',
            'username' => 'without-group-parent',
            'password' => 'password123',
            'phone' => '+995555410031',
            'role' => 'parent',
            'status' => 'active',
            'phone_verified_at' => now(),
            'club_access_approved_at' => now(),
        ]);

        $child = Child::create([
            'first_name' => 'ნინი',
            'last_name' => 'ტესტი',
            'birth_date' => '2022-02-02',
            'birth_year' => 2022,
        ]);
        $parent->children()->attach($child->id, [
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['segment' => 'approved_incomplete']))
            ->assertOk()
            ->assertSee('ჯგუფის გარეშე მშობელი')
            ->assertSee('საჭიროა აქტიურ ჯგუფში ჩარიცხვა');
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'ადმინისტრატორი',
            'username' => 'registry-admin',
            'password' => 'password123',
            'phone' => '+995555419999',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }

    private function group(): KindergartenGroup
    {
        return KindergartenGroup::create([
            'name' => '4-5 წელი',
            'slug' => '4-5-test',
            'age_min_months' => 48,
            'age_max_months' => 59,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }

    private function clubParent(string $name, string $phone, KindergartenGroup $group): User
    {
        $parent = User::create([
            'name' => $name,
            'username' => str_replace(' ', '-', $name),
            'password' => 'password123',
            'phone' => $phone,
            'role' => 'parent',
            'status' => 'active',
            'phone_verified_at' => now(),
            'club_access_approved_at' => now(),
        ]);

        $child = Child::create([
            'first_name' => 'ბავშვი',
            'last_name' => $name,
            'birth_date' => '2022-01-01',
            'birth_year' => 2022,
        ]);

        $parent->children()->attach($child->id, [
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);

        Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => now()->startOfMonth(),
            'enrolled_at' => now(),
        ]);

        return $parent;
    }

    private function incompleteParent(string $name, string $phone): User
    {
        return User::create([
            'name' => $name,
            'username' => str_replace(' ', '-', $name),
            'password' => 'password123',
            'phone' => $phone,
            'role' => 'parent',
            'status' => 'active',
            'phone_verified_at' => now(),
            'club_access_approved_at' => now(),
        ]);
    }

    private function cancelledParent(string $name, string $phone): User
    {
        return User::create([
            'name' => $name,
            'username' => str_replace(' ', '-', $name),
            'password' => 'password123',
            'phone' => $phone,
            'role' => 'parent',
            'status' => 'cancelled',
            'phone_verified_at' => now(),
            'club_access_approved_at' => now(),
        ]);
    }
}
