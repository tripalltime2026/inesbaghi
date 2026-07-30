<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobilePolishTest extends TestCase
{
    public function test_public_pages_load_mobile_polish_through_ines_ai_stylesheet(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/css/ines-ai-chat.css?v=20260730', false);

        $this->get('/chven-shesakheb')
            ->assertOk()
            ->assertSee('/css/ines-ai-chat.css?v=20260730', false);

        $chatCss = file_get_contents(public_path('css/ines-ai-chat.css'));
        $this->assertIsString($chatCss);
        $this->assertStringContainsString("@import url('/css/mobile-polish-v4.css?v=20260730d');", $chatCss);
        $this->assertStringContainsString('bottom:calc(92px + env(safe-area-inset-bottom))', $chatCss);
    }

    public function test_mobile_polish_compacts_the_first_screen_and_separates_floating_controls(): void
    {
        $polishCss = file_get_contents(public_path('css/mobile-polish-v4.css'));
        $this->assertIsString($polishCss);
        $this->assertStringContainsString('.final-site .mobile-app-nav', $polishCss);
        $this->assertStringContainsString('.final-site .ines-ai-launcher', $polishCss);
        $this->assertStringContainsString('height: clamp(188px, 52vw, 226px)', $polishCss);
        $this->assertStringContainsString('-webkit-line-clamp: 3', $polishCss);
        $this->assertStringContainsString('padding-bottom: calc(92px + env(safe-area-inset-bottom))', $polishCss);
    }
}
