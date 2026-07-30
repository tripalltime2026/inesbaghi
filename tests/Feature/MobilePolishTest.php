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

        $this->get('/css/ines-ai-chat.css')
            ->assertOk()
            ->assertSee("@import url('/css/mobile-polish-v4.css?v=20260730d');", false)
            ->assertSee('bottom:calc(92px + env(safe-area-inset-bottom))', false);
    }

    public function test_mobile_polish_compacts_the_first_screen_and_separates_floating_controls(): void
    {
        $this->get('/css/mobile-polish-v4.css')
            ->assertOk()
            ->assertSee('.final-site .mobile-app-nav', false)
            ->assertSee('.final-site .ines-ai-launcher', false)
            ->assertSee('height: clamp(188px, 52vw, 226px)', false)
            ->assertSee('-webkit-line-clamp: 3', false)
            ->assertSee('padding-bottom: calc(92px + env(safe-area-inset-bottom))', false);
    }
}
