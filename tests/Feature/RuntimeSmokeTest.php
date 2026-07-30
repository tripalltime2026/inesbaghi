<?php

namespace Tests\Feature;

use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuntimeSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_legal_pages_and_demo_portals_render_without_server_errors(): void
    {
        config([
            'services.demo_auth.enabled' => true,
            'services.demo_auth.admin_phone' => '555411831',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('სიყვარულით')
            ->assertSee('ჩარიცხვის განაცხადი')
            ->assertSee('/css/mobile.css?v=20260729', false)
            ->assertSee('/css/experience-v2.css?v=20260729b', false)
            ->assertSee('/css/home-mobile-v3.css?v=20260729c', false)
            ->assertSee('/css/privacy-compliance.css?v=20260730', false)
            ->assertSee('/js/experience-v2.js?v=20260729b', false)
            ->assertSee('/js/experience-v2-compat.js?v=20260729b', false)
            ->assertSee('/js/privacy-compliance.js?v=20260730', false)
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
            ->assertSee('მონაცემთა დაცვა')
            ->assertSee('მენიუში ძებნა')
            ->assertSee('/css/privacy-compliance.css?v=20260730', false)
            ->assertSee('/js/privacy-compliance.js?v=20260730', false)
            ->assertSee('viewport-fit=cover', false);

        $this->post('/logout')->assertRedirect('/');

        $this->postJson('/auth/demo/login', [
            'name' => 'დემო მშობელი',
            'phone' => '555123456',
            'privacy_accepted' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertOk()->assertJsonPath('user.role', 'parent');

        $this->get('/parent')
            ->assertOk()
            ->assertSee('/css/experience-v2.css?v=20260729b', false)
            ->assertSee('/css/home-mobile-v3.css?v=20260729c', false)
            ->assertSee('/css/privacy-compliance.css?v=20260730', false)
            ->assertSee('/js/privacy-compliance.js?v=20260730', false)
            ->assertSee('viewport-fit=cover', false);
    }
}
