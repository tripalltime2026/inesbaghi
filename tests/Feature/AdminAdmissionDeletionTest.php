<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdmissionDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_application_without_deleting_converted_child(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admission-delete-admin',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $child = Child::create([
            'first_name' => 'ანა',
            'last_name' => 'ბერიძე',
            'birth_year' => 2022,
        ]);

        $application = AdmissionApplication::create([
            'parent_name' => 'ნინო ბერიძე',
            'phone' => '+995555300001',
            'child_name' => 'ანა ბერიძე',
            'birth_year' => 2022,
            'preferred_group' => '3-4',
            'academic_year' => '2026',
            'status' => 'enrolled',
            'source' => 'website',
            'converted_child_id' => $child->id,
            'converted_at' => now(),
        ]);

        $application->notes()->create([
            'author_user_id' => $admin->id,
            'body' => 'სატესტო კომენტარი',
            'is_internal' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.admissions.update', $application), ['intent' => 'delete'])
            ->assertRedirect(route('admin.admissions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('admission_applications', ['id' => $application->id]);
        $this->assertDatabaseMissing('admission_notes', ['admission_application_id' => $application->id]);
        $this->assertDatabaseHas('children', ['id' => $child->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admission.deleted',
            'subject_id' => $application->id,
        ]);
    }
}
