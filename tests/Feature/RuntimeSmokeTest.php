<?php

namespace Tests\Feature;

use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuntimeSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_legal_pages_and_account_portals_render_without_server_errors(): void
    {
        config([
            'services.demo_auth.enabled' => true,
            'services.demo_auth.admin_phone' => '555411831',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('სიყვარულით')
            ->assertSee('ჩარიცხვის განაცხადი')
            ->assertSee('კონფიდენციალურობა')
            ->assertSee('სარგებლობის პირობები')
            ->assertSee('მონაცემთა მოთხოვნა')
            ->assertDontSee('შპს ინეს ბაღი · ს/კ 445602465')
            ->assertSee('/css/mobile.css?v=20260729', false)
            ->assertSee('/css/experience-v2.css?v=20260729b', false)
            ->assertSee('/css/home-mobile-v3.css?v=20260729c', false)
            ->assertSee('/css/privacy-compliance.css?v=20260730b', false)
            ->assertSee('/css/access-control.css?v=20260730', false)
            ->assertSee('/js/experience-v2.js?v=20260729b', false)
            ->assertSee('/js/experience-v2-compat.js?v=20260729b', false)
            ->assertSee('/js/privacy-compliance.js?v=20260730b', false)
            ->assertSee('/js/auth-access-control.js?v=20260730', false)
            ->assertSee('viewport-fit=cover', false);

        $this->get('/privacy')
            ->assertOk()
            ->assertSee('კონფიდენციალურობის პოლიტიკა')
            ->assertSee('445602465')
            ->assertSee('შპს ინეს ბაღი');

        $this->get('/terms')
            ->assertOk()
            ->assertSee('სარგებლობის პირობები');

        $this->get('/privacy/request')
            ->assertOk()
            ->assertSee('მონაცემთა სუბიექტის მოთხოვნა');

        $this->getJson('/auth/mode')
            ->assertOk()
            ->assertJsonPath('demo_enabled', true)
            ->assertJsonPath('privacy_policy_version', PrivacyPolicy::VERSION);

        $this->postJson('/auth/demo/login', [
            'name' => 'ინეს ბაღის ადმინისტრატორი',
            'phone' => '555411831',
        ])->assertOk()->assertJsonPath('user.role', 'admin');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('მართვის ცენტრი')
            ->assertSee('პლატფორმის მართვა')
            ->assertSee('მომხმარებელთა რეესტრი')
            ->assertSee('მონაცემთა დაცვა')
            ->assertSee('მენიუში ძებნა')
            ->assertSee('/css/privacy-compliance.css?v=20260730b', false)
            ->assertSee('/css/access-control.css?v=20260730', false)
            ->assertSee('/js/privacy-compliance.js?v=20260730b', false)
            ->assertSee('viewport-fit=cover', false);

        $this->post('/logout')->assertRedirect('/');

        $this->postJson('/auth/demo/login', [
            'name' => 'დემო მომხმარებელი',
            'phone' => '555123456',
            'privacy_accepted' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertOk()
            ->assertJsonPath('user.role', 'member')
            ->assertJsonPath('user.parent_club_access', false)
            ->assertJsonPath('redirect_to', route('account.status'));

        $this->get('/account')
            ->assertOk()
            ->assertSee('ანგარიშის ცენტრი')
            ->assertSee('კლუბის წვდომა')
            ->assertSee('მარკეტინგული და საინფორმაციო შეტყობინებების მიღება არჩევითია');
    }
}
