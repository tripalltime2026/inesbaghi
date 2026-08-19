<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_roles_are_limited_to_their_workspaces(): void
    {
        $finance = $this->user('finance', '+995555100001');
        $teacher = $this->user('teacher', '+995555100002');

        $this->actingAs($finance)->get('/admin/payments')->assertOk();
        $this->actingAs($finance)->get('/admin/admissions')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/attendance')->assertOk();
        $this->actingAs($teacher)->get('/admin/payments')->assertForbidden();
    }

    public function test_monthly_billing_is_generated_once_for_active_enrollment(): void
    {
        [$group, $child, $enrollment] = $this->activeEnrollment();
        $admin = $this->user('admin', '+995555100003');

        $payload = ['period' => '2026-09', 'due_at' => '2026-09-10', 'group_id' => $group->id];
        $this->actingAs($admin)->post('/admin/payments/generate', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/payments/generate', $payload)->assertRedirect();

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'enrollment_id' => $enrollment->id,
            'period' => '2026-09',
            'period_starts_on' => '2026-09-01 00:00:00',
            'period_ends_on' => '2026-09-30 00:00:00',
            'amount' => 600,
            'paid_amount' => 0,
            'status' => 'pending',
            'confirmed_at' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.generated']);
    }

    public function test_scheduled_billing_command_is_available_and_idempotent(): void
    {
        $this->activeEnrollment();

        $this->artisan('billing:generate', ['period' => '2026-10', '--due' => '2026-10-10'])
            ->assertSuccessful();
        $this->artisan('billing:generate', ['period' => '2026-10', '--due' => '2026-10-10'])
            ->assertSuccessful();

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_partial_payment_updates_balance_and_prevents_overpayment(): void
    {
        [, , $enrollment] = $this->activeEnrollment();
        $finance = $this->user('finance', '+995555100004');
        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'period' => '2026-09',
            'period_starts_on' => '2026-09-01',
            'period_ends_on' => '2026-09-30',
            'amount' => 600,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'currency' => 'GEL',
            'status' => 'pending',
            'due_at' => '2026-09-10 23:59:59',
            'confirmed_at' => now(),
            'confirmed_by_user_id' => $finance->id,
        ]);

        $this->actingAs($finance)->post("/admin/payments/{$payment->id}/transactions", [
            'amount' => 200,
            'method' => 'bank_transfer',
            'reference' => 'BANK-001',
            'paid_at' => '2026-09-05 12:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'paid_amount' => 200, 'status' => 'partial']);
        $this->assertDatabaseHas('payment_transactions', ['payment_id' => $payment->id, 'amount' => 200, 'method' => 'bank_transfer']);

        $this->actingAs($finance)->post("/admin/payments/{$payment->id}/transactions", [
            'amount' => 401,
            'method' => 'cash',
            'paid_at' => '2026-09-06 12:00:00',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_teacher_can_check_child_in_and_out(): void
    {
        [$group, $child] = $this->activeEnrollment();
        $teacher = $this->user('teacher', '+995555100005');
        $date = today()->toDateString();

        $this->actingAs($teacher)->put("/admin/attendance/{$child->id}", [
            'date' => $date,
            'group_id' => $group->id,
            'action' => 'check_in',
        ])->assertRedirect();

        $this->actingAs($teacher)->put("/admin/attendance/{$child->id}", [
            'date' => $date,
            'group_id' => $group->id,
            'action' => 'check_out',
            'pickup_by_name' => 'ნინო ბერიძე',
        ])->assertRedirect();

        $record = AttendanceRecord::firstOrFail();
        $this->assertSame('present', $record->status);
        $this->assertNotNull($record->checked_in_at);
        $this->assertNotNull($record->checked_out_at);
        $this->assertSame('ნინო ბერიძე', $record->pickup_by_name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.updated', 'subject_id' => $record->id]);
    }

    public function test_parent_sees_only_own_attendance_and_balance(): void
    {
        [$group, $ownChild, $enrollment] = $this->activeEnrollment();
        $parent = $this->user('parent', '+995555100006');
        $ownChild->guardians()->attach($parent->id, ['relationship' => 'parent', 'is_primary' => true, 'can_pick_up' => true]);
        AttendanceRecord::create([
            'child_id' => $ownChild->id,
            'kindergarten_group_id' => $group->id,
            'attendance_date' => today(),
            'status' => 'present',
            'checked_in_at' => now()->subHours(2),
        ]);
        Payment::create([
            'enrollment_id' => $enrollment->id,
            'period' => now()->format('Y-m'),
            'period_starts_on' => now()->startOfMonth()->toDateString(),
            'period_ends_on' => now()->endOfMonth()->toDateString(),
            'amount' => 600,
            'discount_amount' => 50,
            'paid_amount' => 200,
            'currency' => 'GEL',
            'status' => 'partial',
            'due_at' => now()->addDays(5),
            'confirmed_at' => now(),
        ]);

        [, $otherChild, $otherEnrollment] = $this->activeEnrollment('სხვა', '+995555199999');
        Payment::create([
            'enrollment_id' => $otherEnrollment->id,
            'period' => now()->format('Y-m'),
            'period_starts_on' => now()->startOfMonth()->toDateString(),
            'period_ends_on' => now()->endOfMonth()->toDateString(),
            'amount' => 999,
            'discount_amount' => 0,
            'paid_amount' => 0,
            'currency' => 'GEL',
            'status' => 'pending',
            'due_at' => now()->addDays(5),
            'confirmed_at' => now(),
        ]);

        $this->actingAs($parent)
            ->get('/parent')
            ->assertOk()
            ->assertSee($ownChild->first_name)
            ->assertSee('დასწრებული')
            ->assertSee('350.00')
            ->assertDontSee($otherChild->first_name)
            ->assertDontSee('999.00');
    }

    private function activeEnrollment(string $childName = 'ანა', ?string $unique = null): array
    {
        $suffix = $unique ? preg_replace('/\D+/', '', $unique) : (string) random_int(100000, 999999);
        $group = KindergartenGroup::create([
            'name' => '3-4 წელი '.$suffix,
            'slug' => 'group-'.$suffix,
            'age_min_months' => 36,
            'age_max_months' => 47,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
        $child = Child::create(['first_name' => $childName, 'last_name' => 'ბერიძე', 'birth_year' => 2022]);
        $enrollment = Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => '2026-01-01',
            'enrolled_at' => now(),
        ]);

        return [$group, $child, $enrollment];
    }

    private function user(string $role, string $phone): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'phone' => $phone,
            'role' => $role,
            'status' => 'active',
            'phone_verified_at' => now(),
            'club_access_approved_at' => $role === 'parent' ? now() : null,
        ]);
    }
}
