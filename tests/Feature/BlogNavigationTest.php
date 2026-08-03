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
            ->assertSee('/js/blog-navigation.js?v=20260803c', false);

        $this->assertGreaterThanOrEqual(
            2,
            substr_count((string) $response->getContent(), 'href="'.$blogUrl.'"'),
        );

        $this->get('/blogi')
            ->assertOk()
            ->assertSee('ბლოგი მშობლებისთვის')
            ->assertDontSee('სასარგებლო რჩევები ყოველდღიური მშობლობისთვის');
    }
}
