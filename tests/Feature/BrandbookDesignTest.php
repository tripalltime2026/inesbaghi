<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandbookDesignTest extends TestCase
{
    public function test_public_home_loads_brandbook_design_assets(): void
    {
        $response = $this->get('/');

        $response
            ->assertSuccessful()
            ->assertSee('/css/brand-premium.css?v=20260803a', false)
            ->assertSee('/images/ines-logo-horizontal.svg', false)
            ->assertSee('/images/ines-logo-favicon.svg', false);
    }

    public function test_brandbook_assets_exist_and_use_official_palette(): void
    {
        $this->assertFileExists(public_path('css/brand-premium.css'));
        $this->assertFileExists(public_path('images/ines-logo-horizontal.svg'));
        $this->assertFileExists(public_path('images/ines-logo-main.svg'));
        $this->assertFileExists(public_path('images/ines-logo-favicon.svg'));

        $css = file_get_contents(public_path('css/brand-premium.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('#A3D1CB', $css);
        $this->assertStringContainsString('#D6BACE', $css);
        $this->assertStringContainsString('#0F2C35', $css);
        $this->assertStringContainsString("font-family:'FiraGO'", $css);
    }
}
