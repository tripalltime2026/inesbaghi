<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiChildBillingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_add_second_and_third_child_without_replacing_existing_children(): void
    {
        $parent = $this->parent('multi-child-parent', '+995555200001');
        $first = Child::create([
            'first_name' => 'ანა',
            'last_name' => 'ბერიძე',
            'birth_date' => '2021-05-10',
            'birth_year' => 2021,
        ]);
        $parent->children()->attach($first->id, [
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);

        foreach ([
            ['ნიკა', '2022-04-11'],
            ['მარი', '2023-03-12'],
        ] as [$name, $birthDate]) {
            $this->actingAs($parent)
                ->post(route('account.children.store'), [
                    'child_first_name' => $name,
                    'child_last_name' => 'ბერიძე',
                    'child_birth_date' => $birthDate,
                ])
                ->assertRedirect(route('account.profile'))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, $parent->fresh()->children()->count());
        $this->assertDatabaseHas('child_guardians', ['user_id' => $parent->id, 'child_id' => $first->id]);
        $this->assertDatabaseHas('children', ['first_name' => 'ნიკა', 'last_name' => 'ბერიძე']);
        $this->assertDatabaseHas('children', ['first_name' => 'მარი', 'last_name' => 'ბერიძე']);
    }

    public function test_parent_sees_each_own_child_group_and_separate_confirmed_amounts_only(): void
    {
        $parent = $this->parent('family-parent', '+995555200002', true);
        [$firstChild, $firstEnrollment] = $this->attachChildInGroup($parent, 'ანა', '3-4 წელი', 'family-group-a', 600);
        [$secondChild, $secondEnrollment] = $this->attachChildInGroup($parent, 'ნიკა', '5-6 წელი', 'family-group-b', 500);

        $this->confirmedPayment($firstEnrollment, 550, 50, '2026-08');
        $this->confirmedPayment($secondEnrollment, 420, 0, '2026-08');

        $otherParent = $this->parent('other-family', '+995555200003', true);
        [$otherChild, $otherEnrollment] = $this->attachChildInGroup($otherParent, 'სხვა', '4-5 წელი', 'other-family-group', 999);
        $this->confirmedPayment($otherEnrollment, 999, 0, '2026-08');

        $response = $this->actingAs($parent)->get(route('parent.dashboard'));

        $response
            ->assertOk()
            ->assertSee($firstChild->first_name)
            ->assertSee($secondChild->first_name)
            ->assertSee('3-4 წელი')
            ->assertSee('5-6 წელი')
            ->assertSee('500.00')
            ->assertSee('420.00')
            ->assertSee('920.00')
            ->assertDontSee($otherChild->first_name)
            ->assertDontSee('999.00');
    }

    public function test_unconfirmed_charge_is_not_visible_to_parent(): void
    {
        $parent = $this->parent('draft-hidden-parent', '+995555200004', true);
        [$child, $enrollment] = $this->attachChildInGroup($parent, 'ელენე', '3-4 წელი', 'draft-hidden-group', 600);

        Payment::create([
            'enrollment_id' => $enrollment->id,
            'period' => '2026-08',
            'period_starts_on' => '2026-08-01',
            'period_ends_on' => '2026-08-31',
            'amount' => 777,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'currency' => 'GEL',
            'status' => 'pending',
            'due_at' => '2026-08-10 23:59:59',
            'confirmed_at' => null,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee($child->first_name)
            ->assertDontSee('777.00');
    }

    public function test_admin_can_set_different_amounts_and_confirm_whole_month(): void
    {
        $admin = $this->admin();
        $parent = $this->parent('billing-parent', '+995555200005', true);
        [, $firstEnrollment] = $this->attachChildInGroup($parent, 'ანა', '3-4 წელი', 'billing-a', 600);
        [, $secondEnrollment] = $this->attachChildInGroup($parent, 'ნიკა', '5-6 წელი', 'billing-b', 500);

        $firstPayment = Payment::create([
            'enrollment_id' => $firstEnrollment->id,
            'period' => '2026-09',
            'period_starts_on' => '2026-09-01',
            'period_ends_on' => '2026-09-30',
            'amount' => 600,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'currency' => 'GEL',
            'status' => 'pending',
            'due_at' => '2026-09-10 23:59:59',
        ]);
        $secondPayment = Payment::create([
            'enrollment_id' => $secondEnrollment->id,
            'period' => '2026-09',
            'period_starts_on' => '2026-09-01',
            'period_ends_on' => '2026-09-30',
            'amount' => 500,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'currency' => 'GEL',
            'status' => 'pending',
            'due_at' => '2026-09-10 23:59:59',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.payments.update', $secondPayment), [
                'amount' => 430,
                'discount_amount' => 30,
                'period_starts_on' => '2026-09-05',
                'period_ends_on' => '2026-09-30',
                'due_at' => '2026-09-12',
                'status' => 'pending',
                'notes' => 'ინდივიდუალური ოჯახური პირობები',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $secondPayment->refresh();
        $this->assertSame(400.0, $secondPayment->totalDue());
        $this->assertSame('2026-09-05', $secondPayment->period_starts_on->toDateString());
        $this->assertNull($secondPayment->confirmed_at);

        $this->actingAs($admin)
            ->post(route('admin.payments.confirm-period'), ['period' => '2026-09'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotNull($firstPayment->fresh()->confirmed_at);
        $this->assertNotNull($secondPayment->fresh()->confirmed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.period_confirmed']);
    }

    private function parent(string $username, string $phone, bool $approved = false): User
    {
        return User::create([
            'name' => 'Parent '.$username,
            'username' => $username,
            'password' => 'password123',
            'phone' => $phone,
            'email' => $username.'@example.com',
            'role' => 'parent',
            'status' => 'active',
            'club_access_approved_at' => $approved ? now() : null,
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'username' => 'multi-billing-admin',
            'password' => 'password123',
            'phone' => '+995555200099',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function attachChildInGroup(User $parent, string $name, string $groupName, string $slug, float $fee): array
    {
        $group = KindergartenGroup::create([
            'name' => $groupName,
            'slug' => $slug,
            'age_min_months' => 36,
            'age_max_months' => 72,
            'capacity' => 20,
            'monthly_fee' => $fee,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
        $child = Child::create([
            'first_name' => $name,
            'last_name' => 'ბერიძე',
            'birth_date' => '2022-05-10',
            'birth_year' => 2022,
        ]);
        $parent->children()->attach($child->id, [
            'relationship' => 'მშობელი',
            'is_primary' => ! $parent->children()->whereKeyNot($child->id)->exists(),
            'can_pick_up' => true,
        ]);
        $enrollment = Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => '2026-01-01',
            'enrolled_at' => now(),
        ]);

        return [$child, $enrollment];
    }

    private function confirmedPayment(Enrollment $enrollment, float $amount, float $discount, string $period): Payment
    {
        return Payment::create([
            'enrollment_id' => $enrollment->id,
            'period' => $period,
            'period_starts_on' => $period.'-01',
            'period_ends_on' => $period.'-31',
            'amount' => $amount,
            'discount_amount' => $discount,
            'paid_amount' => 0,
            'currency' => 'GEL',
            'status' => 'pending',
            'due_at' => $period.'-10 23:59:59',
            'confirmed_at' => now(),
        ]);
    }
}
