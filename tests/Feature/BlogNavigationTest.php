<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_blog_controls_open_the_public_blog_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/js/blog-navigation.js?v=20260803a', false)
            ->assertSee('data-page-target="blog"', false);

        $script = file_get_contents(public_path('js/blog-navigation.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('[data-page-target="blog"]', $script);
        $this->assertStringContainsString("window.location.assign(blogPath)", $script);

        $this->get('/blogi')
            ->assertOk()
            ->assertSee('ბლოგი მშობლებისთვის');
    }
}
