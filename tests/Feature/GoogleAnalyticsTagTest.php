<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoogleAnalyticsTagTest extends TestCase
{
    public function test_google_tag_is_injected_once_inside_head_after_gtm(): void
    {
        config()->set('analytics.google_measurement_id', 'G-19W9V5TEZ9');
        config()->set('tag_manager.container_id', 'GTM-M5MC34KM');

        foreach (['/', '/chven-shesakheb', '/shesvla', '/registratsia', '/privacy'] as $path) {
            $response = $this->get($path)->assertOk();
            $html = $response->getContent();

            $this->assertIsString($html);
            $this->assertSame(1, substr_count($html, 'googletagmanager.com/gtag/js?id=G-19W9V5TEZ9'));
            $this->assertSame(1, substr_count($html, "gtag('config', 'G-19W9V5TEZ9')"));

            $gtmPosition = strpos($html, '<!-- Google Tag Manager -->');
            $analyticsPosition = strpos($html, '<!-- Google tag (gtag.js) -->');
            $headEndPosition = stripos($html, '</head>');

            $this->assertNotFalse($gtmPosition);
            $this->assertNotFalse($analyticsPosition);
            $this->assertNotFalse($headEndPosition);
            $this->assertTrue($gtmPosition < $analyticsPosition);
            $this->assertTrue($analyticsPosition < $headEndPosition);
        }
    }

    public function test_google_tag_is_not_added_to_json_responses(): void
    {
        config()->set('analytics.google_measurement_id', 'G-19W9V5TEZ9');

        $this->getJson('/auth/mode')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('G-19W9V5TEZ9', false);
    }

    public function test_invalid_measurement_id_disables_injection_safely(): void
    {
        config()->set('analytics.google_measurement_id', 'invalid-id');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtag/js', false);
    }
}
