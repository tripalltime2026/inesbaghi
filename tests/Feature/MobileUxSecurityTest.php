<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileUxSecurityTest extends TestCase
{
    public function test_public_auth_mode_never_exposes_the_admin_phone(): void
    {
        config()->set('services.demo_auth.enabled', true);
        config()->set('services.demo_auth.admin_phone', '555411831');

        $response = $this->getJson('/auth/mode')->assertOk();

        $this->assertTrue((bool) $response->json('demo_enabled'));
        $this->assertArrayNotHasKey('admin_phone', $response->json());
        $response->assertDontSee('555411831');
    }

    public function test_public_pages_load_the_latest_mobile_fix_assets(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/css/mobile-fixes-v5.css?v=20260731a', false)
            ->assertSee('/js/mobile-fixes-v5.js?v=20260731a', false);

        $this->get('/chven-shesakheb')
            ->assertOk()
            ->assertSee('/css/mobile-fixes-v5.css?v=20260731a', false)
            ->assertSee('/js/mobile-fixes-v5.js?v=20260731a', false);
    }

    public function test_mobile_layout_prevents_footer_and_overlay_collisions(): void
    {
        $css = file_get_contents(public_path('css/mobile-fixes-v5.css'));
        $mobileJs = file_get_contents(public_path('js/mobile-fixes-v5.js'));
        $siteJs = file_get_contents(public_path('js/site.js'));

        $this->assertIsString($css);
        $this->assertIsString($mobileJs);
        $this->assertIsString($siteJs);

        $this->assertStringContainsString('.site-footer .seo-footer-nav', $css);
        $this->assertStringContainsString('display: none !important', $css);
        $this->assertStringContainsString('.final-site .ines-ai-launcher', $css);
        $this->assertStringContainsString('position: fixed', $css);
        $this->assertStringContainsString('font-size: 14px', $css);

        $this->assertStringContainsString('data-mobile-key="ai"', $mobileJs);
        $this->assertStringContainsString('Ines AI', $mobileJs);
        $this->assertStringNotContainsString('დემო ადმინისტრატორი', $siteJs);
        $this->assertStringNotContainsString("admin_phone: '555411831'", $siteJs);
    }
}
