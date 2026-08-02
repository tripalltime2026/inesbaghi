<?php

namespace Tests\Feature;

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
}
