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
        foreach (['/', '/chven-shesakheb'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('/css/mobile-fixes-v5.css?v=20260731d', false)
                ->assertSee('/css/mobile-stability-v6.css?v=20260731a', false)
                ->assertSee('/css/mobile-stability-v7.css?v=20260731a', false)
                ->assertSee('/js/mobile-fixes-v5.js?v=20260731d', false)
                ->assertSee('password-mobile-nav', false);
        }
    }

    public function test_mobile_layout_uses_real_links_and_separates_ines_ai(): void
    {
        $css = file_get_contents(public_path('css/mobile-fixes-v5.css'));
        $stabilityCss = file_get_contents(public_path('css/mobile-stability-v7.css'));
        $mobileJs = file_get_contents(public_path('js/mobile-fixes-v5.js'));
        $siteJs = file_get_contents(public_path('js/site.js'));

        $this->assertIsString($css);
        $this->assertIsString($stabilityCss);
        $this->assertIsString($mobileJs);
        $this->assertIsString($siteJs);

        $this->assertStringContainsString('.site-footer .seo-footer-nav', $css);
        $this->assertStringContainsString('.ines-ai-launcher', $stabilityCss);
        $this->assertStringContainsString('bottom:calc(88px + env(safe-area-inset-bottom))', $stabilityCss);
        $this->assertStringContainsString('[data-mobile-key="account"]', $stabilityCss);

        $this->assertStringContainsString("groups: '/jgufebi'", $mobileJs);
        $this->assertStringContainsString("admission: '/charetskhva'", $mobileJs);
        $this->assertStringContainsString("replaceWithLink", $mobileJs);
        $this->assertStringNotContainsString('activatePublicPage', $mobileJs);
        $this->assertStringNotContainsString('event.stopImmediatePropagation()', $mobileJs);
        $this->assertStringNotContainsString('დემო ადმინისტრატორი', $siteJs);
        $this->assertStringNotContainsString("admin_phone: '555411831'", $siteJs);
    }
}
