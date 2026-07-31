<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoogleTagManagerTest extends TestCase
{
    public function test_gtm_is_injected_once_after_head_and_body_on_html_pages(): void
    {
        config()->set('tag_manager.container_id', 'GTM-M5MC34KM');

        foreach (['/', '/chven-shesakheb', '/shesvla', '/registratsia', '/privacy'] as $path) {
            $response = $this->get($path)->assertOk();
            $html = $response->getContent();

            $this->assertIsString($html);
            $this->assertSame(1, substr_count($html, "})(window,document,'script','dataLayer','GTM-M5MC34KM');</script>"));
            $this->assertSame(1, substr_count($html, 'googletagmanager.com/ns.html?id=GTM-M5MC34KM'));
            $this->assertMatchesRegularExpression(
                '/<head(?:\s[^>]*)?>\s*<!-- Google Tag Manager -->/i',
                $html,
            );
            $this->assertMatchesRegularExpression(
                '/<body(?:\s[^>]*)?>\s*<!-- Google Tag Manager \(noscript\) -->/i',
                $html,
            );
        }
    }

    public function test_gtm_is_not_added_to_json_responses(): void
    {
        config()->set('tag_manager.container_id', 'GTM-M5MC34KM');

        $this->getJson('/auth/mode')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('GTM-M5MC34KM', false);
    }

    public function test_invalid_container_id_disables_gtm_safely(): void
    {
        config()->set('tag_manager.container_id', 'invalid-id');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtm.js', false)
            ->assertDontSee('googletagmanager.com/ns.html', false);
    }
}
