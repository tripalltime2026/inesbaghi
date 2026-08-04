<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_blog_controls_are_real_links_to_the_public_blog_page(): void
    {
        $blogUrl = route('public.blog');
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('ბლოგი')
            ->assertSee('ყველა სტატია →')
            ->assertSee($blogUrl, false)
            ->assertSee('/js/blog-navigation.js?v=20260803c', false)
            ->assertSee('/js/cms-public.js?v=20260804a', false);

        $this->assertGreaterThanOrEqual(
            2,
            substr_count((string) $response->getContent(), 'href="'.$blogUrl.'"'),
        );

        $this->get('/blogi')
            ->assertOk()
            ->assertSee('ბლოგი მშობლებისთვის')
            ->assertDontSee('სასარგებლო რჩევები ყოველდღიური მშობლობისთვის');
    }

    public function test_cms_renderer_keeps_blog_cards_clickable(): void
    {
        $script = file_get_contents(public_path('js/cms-public.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('const blogUrl = (post = {}) =>', $script);
        $this->assertStringContainsString('miniGrid && !miniGrid.querySelector(\'a[href]\')', $script);
        $this->assertStringContainsString('posts.slice(0, 3).map(miniBlogCard)', $script);
        $this->assertStringContainsString('href="${url}"', $script);
    }
}
