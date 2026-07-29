<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_member_cannot_access_admin_crm(): void
    {
        $member = User::create([
            'name' => 'Pending User',
            'phone' => '+995555000001',
            'role' => 'member',
            'status' => 'pending',
        ]);

        $this->actingAs($member)
            ->get('/admin/admissions')
            ->assertForbidden();
    }

    public function test_admin_can_filter_update_and_comment_on_application(): void
    {
        $admin = $this->admin();
        $application = $this->application();

        $this->actingAs($admin)
            ->get('/admin/admissions?status=new&search=ანა')
            ->assertOk()
            ->assertSee('ანა');

        $this->actingAs($admin)
            ->patch("/admin/admissions/{$application->id}", [
                'status' => 'contacted',
                'assigned_to_user_id' => $admin->id,
                'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'tour_scheduled_at' => null,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post("/admin/admissions/{$application->id}/notes", [
                'body' => 'მშობელს დავუკავშირდით და შემდეგი ზარი დაიგეგმა.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admission_applications', [
            'id' => $application->id,
            'status' => 'contacted',
            'assigned_to_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('admission_notes', [
            'admission_application_id' => $application->id,
            'author_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admission.updated',
            'subject_id' => $application->id,
        ]);
    }

    public function test_admin_can_convert_application_once(): void
    {
        $admin = $this->admin();
        KindergartenGroup::create([
            'name' => '3-4 წელი',
            'slug' => '3-4',
            'age_min_months' => 36,
            'age_max_months' => 47,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
        $application = $this->application();

        $this->actingAs($admin)
            ->post("/admin/admissions/{$application->id}/convert")
            ->assertRedirect();

        $application->refresh();
        $this->assertNotNull($application->converted_at);
        $this->assertNotNull($application->converted_child_id);
        $this->assertSame('enrolled', $application->status);

        $this->assertDatabaseHas('users', [
            'phone' => '+995555123456',
            'role' => 'parent',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('children', [
            'id' => $application->converted_child_id,
            'first_name' => 'ანა',
            'last_name' => 'ბერიძე',
            'birth_year' => 2022,
        ]);
        $this->assertDatabaseHas('child_guardians', [
            'child_id' => $application->converted_child_id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'child_id' => $application->converted_child_id,
            'status' => 'pending',
            'starts_on' => '2026-09-01 00:00:00',
        ]);

        $childCount = \App\Models\Child::count();
        $this->actingAs($admin)
            ->post("/admin/admissions/{$application->id}/convert")
            ->assertRedirect();
        $this->assertSame($childCount, \App\Models\Child::count());
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'ადმინისტრატორი',
            'phone' => '+995555000002',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }

    private function application(): AdmissionApplication
    {
        return AdmissionApplication::create([
            'parent_name' => 'ნინო ბერიძე',
            'phone' => '+995555123456',
            'child_name' => 'ანა ბერიძე',
            'birth_year' => 2022,
            'preferred_group' => '3-4',
            'academic_year' => '2026',
            'wants_tour' => true,
            'status' => 'new',
            'source' => 'website',
        ]);
    }
}
