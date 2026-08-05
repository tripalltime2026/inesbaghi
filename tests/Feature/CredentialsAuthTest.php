<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CredentialsAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_uses_real_routes_and_password_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('password-mobile-nav', false)
            ->assertSee('/jgufebi', false)
            ->assertSee('/charetskhva', false)
            ->assertSee('/shesvla', false)
            ->assertDontSee('/auth/google', false)
            ->assertDontSee('Google-ით გაგრძელება');

        $this->get('/jgufebi')->assertOk()->assertSee('ჯგუფები');
        $this->get('/charetskhva')->assertOk()->assertSee('ვიზიტი');
        $this->get('/shesvla')
            ->assertOk()
            ->assertSee('კეთილი იყოს თქვენი დაბრუნება')
            ->assertSee('ელფოსტა ან მომხმარებლის სახელი')
            ->assertDontSee('ნინო ბერიძე');
        $this->get('/registratsia')
            ->assertOk()
            ->assertSee('<h1>რეგისტრაცია</h1>', false)
            ->assertSee('მშობლის ინფორმაცია')
            ->assertSee('ბავშვის ინფორმაცია')
            ->assertDontSee('ნინო ბერიძე');
        $this->get('/auth/google')->assertNotFound();
    }

    public function test_standard_registration_creates_parent_and_linked_child(): void
    {
        $response = $this->post('/registratsia', [
            'name' => 'თამარ კიკნაძე',
            'email' => 'PARENT@EXAMPLE.COM',
            'phone' => '555 12 34 56',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'child_first_name' => 'ანა',
            'child_last_name' => 'კიკნაძე',
            'child_birth_date' => '2022-05-14',
            'privacy_accepted' => '1',
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ]);

        $response->assertRedirect(route('account.status'));

        $user = User::where('email', 'parent@example.com')->firstOrFail();
        $child = Child::firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('parent', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame('parent@example.com', $user->username);
        $this->assertSame('+995555123456', $user->phone);
        $this->assertTrue(Hash::check('StrongPass123', $user->password));
        $this->assertSame('ანა', $child->first_name);
        $this->assertSame('კიკნაძე', $child->last_name);
        $this->assertSame('2022-05-14', $child->birth_date->format('Y-m-d'));
        $this->assertSame(2022, $child->birth_year);
        $this->assertTrue($user->children()->whereKey($child->id)->exists());
        $this->assertFalse($user->canAccessParentClub());

        $this->assertDatabaseHas('child_guardians', [
            'user_id' => $user->id,
            'child_id' => $child->id,
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);
        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'consent_type' => 'account_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'parent.registered_with_child',
            'subject_id' => $child->id,
        ]);
    }

    public function test_duplicate_email_and_phone_are_rejected(): void
    {
        User::create([
            'name' => 'არსებული მშობელი',
            'username' => 'existing@example.com',
            'email' => 'existing@example.com',
            'phone' => '+995555123456',
            'password' => 'StrongPass123',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $this->from('/registratsia')->post('/registratsia', [
            'name' => 'სხვა მშობელი',
            'email' => 'EXISTING@EXAMPLE.COM',
            'phone' => '555123456',
            'password' => 'AnotherPass123',
            'password_confirmation' => 'AnotherPass123',
            'child_first_name' => 'ლუკა',
            'child_last_name' => 'კიკნაძე',
            'child_birth_date' => '2021-08-09',
            'privacy_accepted' => '1',
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertRedirect('/registratsia')
            ->assertSessionHasErrors(['email', 'phone']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('children', 0);
    }

    public function test_parent_logs_in_with_email(): void
    {
        $user = User::create([
            'name' => 'თამარ კიკნაძე',
            'username' => 'parent-internal',
            'email' => 'parent@example.com',
            'password' => 'StrongPass123',
            'phone' => '+995555123456',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $this->post('/shesvla', [
            'name' => 'PARENT@EXAMPLE.COM',
            'password' => 'StrongPass123',
        ])->assertRedirect(route('account.status'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_edit_child_created_for_parent(): void
    {
        $parent = User::create([
            'name' => 'მშობელი',
            'username' => 'parent@example.com',
            'email' => 'parent@example.com',
            'phone' => '+995555123456',
            'password' => 'StrongPass123',
            'role' => 'parent',
            'status' => 'active',
        ]);
        $child = Child::create([
            'first_name' => 'ანა',
            'last_name' => 'კიკნაძე',
            'birth_date' => '2022-05-14',
            'birth_year' => 2022,
        ]);
        $parent->children()->attach($child->id, [
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);

        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'username' => 'admin',
            'password' => 'StrongAdmin123',
            'phone' => '+995555000099',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->patch(route('admin.children.update', $child), [
            'first_name' => 'ანასტასია',
            'last_name' => 'კიკნაძე',
            'birth_year' => 2022,
            'birth_date' => '2022-05-14',
            'medical_notes' => 'მონაცემები გადაამოწმა ადმინისტრატორმა.',
            'photo_consent' => '0',
        ])->assertRedirect();

        $child->refresh();
        $this->assertSame('ანასტასია', $child->first_name);
        $this->assertSame('მონაცემები გადაამოწმა ადმინისტრატორმა.', $child->medical_notes);
        $this->assertTrue($parent->children()->whereKey($child->id)->exists());
    }

    public function test_existing_admin_keeps_role_with_password_login(): void
    {
        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'username' => 'admin',
            'password' => 'StrongAdmin123',
            'phone' => '+995555000099',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->post('/shesvla', [
            'name' => 'ADMIN',
            'password' => 'StrongAdmin123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('admin', $admin->fresh()->role);
    }
}
