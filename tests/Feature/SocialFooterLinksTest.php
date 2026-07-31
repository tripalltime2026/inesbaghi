<?php

namespace Tests\Feature;

use Tests\TestCase;

class SocialFooterLinksTest extends TestCase
{
    public function test_public_footers_include_accessible_social_links_once(): void
    {
        foreach (['/', '/chven-shesakheb', '/jgufebi', '/kontakti'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertIsString($html);
            $this->assertSame(1, substr_count($html, 'data-social-footer'));
            $this->assertSame(1, substr_count($html, 'https://www.facebook.com/Inesbaghi'));
            $this->assertSame(1, substr_count($html, 'https://www.instagram.com/ines_baghi/'));
            $this->assertSame(1, substr_count($html, '/css/social-footer.css?v=20260731'));
            $this->assertStringContainsString('target="_blank"', $html);
            $this->assertStringContainsString('rel="noopener noreferrer"', $html);
            $this->assertMatchesRegularExpression('/data-social-footer[\s\S]*<\/footer>/i', $html);
        }
    }

    public function test_social_footer_is_not_added_to_json_responses(): void
    {
        $this->getJson('/auth/mode')
            ->assertOk()
            ->assertDontSee('data-social-footer', false)
            ->assertDontSee('facebook.com/Inesbaghi', false)
            ->assertDontSee('instagram.com/ines_baghi', false);
    }
}
