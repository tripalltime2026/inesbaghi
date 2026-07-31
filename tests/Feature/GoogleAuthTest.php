<?php

namespace Tests\Feature;

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
        $this->get('/charetskhva')->assertOk()->assertSee('ჩარიცხვა');
        $this->get('/shesvla')->assertOk()->assertSee('კეთილი იყოს თქვენი დაბრუნება');
        $this->get('/registratsia')->assertOk()->assertSee('რეგისტრაცია ორ ნაბიჯში');
        $this->get('/auth/google')->assertNotFound();
    }

    public function test_registration_creates_only_member_with_hashed_password(): void
    {
        $response = $this->post('/registratsia', [
            'name' => 'ნინო ბერიძე',
            'password' => 'StrongPass123',
            'privacy_accepted' => '1',
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ]);

        $response->assertRedirect(route('account.profile'));

        $user = User::where('username', 'ნინო ბერიძე')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('member', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertNull($user->phone);
        $this->assertTrue(Hash::check('StrongPass123', $user->password));
        $this->assertFalse($user->canAccessParentClub());
        $this->assertDatabaseHas('privacy_consents', [
            'user_id' => $user->id,
            'consent_type' => 'account_privacy_acknowledgement',
            'policy_version' => PrivacyPolicy::VERSION,
        ]);
    }

    public function test_duplicate_login_name_is_rejected_after_normalization(): void
    {
        User::create([
            'name' => 'ნინო ბერიძე',
            'username' => 'ნინო ბერიძე',
            'password' => 'StrongPass123',
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->from('/registratsia')->post('/registratsia', [
            'name' => '  ნინო   ბერიძე  ',
            'password' => 'AnotherPass123',
            'privacy_accepted' => '1',
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertRedirect('/registratsia')
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_member_logs_in_and_completes_phone_profile_without_sms(): void
    {
        $user = User::create([
            'name' => 'თამარ კიკნაძე',
            'username' => 'თამარ კიკნაძე',
            'password' => 'StrongPass123',
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->post('/shesvla', [
            'name' => 'თამარ კიკნაძე',
            'password' => 'StrongPass123',
        ])->assertRedirect(route('account.profile'));

        $this->assertAuthenticatedAs($user);

        $this->patch('/account/profile', [
            'name' => 'თამარ კიკნაძე',
            'phone' => '555123456',
            'email' => 'parent@example.com',
        ])->assertRedirect(route('account.status'));

        $user->refresh();
        $this->assertSame('+995555123456', $user->phone);
        $this->assertSame('parent@example.com', $user->email);
        $this->assertNull($user->phone_verified_at);
        $this->assertTrue($user->hasVerifiedIdentity());
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
