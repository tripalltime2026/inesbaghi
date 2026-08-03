<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_blog_controls_are_real_links_to_the_public_blog_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="/blogi">ბლოგი</a>', false)
            ->assertSee('class="ghost-button" href="/blogi">ყველა სტატია →</a>', false)
            ->assertSee('/js/blog-navigation.js?v=20260803b', false);

        $this->get('/blogi')
            ->assertOk()
            ->assertSee('ბლოგი მშობლებისთვის')
            ->assertDontSee('სასარგებლო რჩევები ყოველდღიური მშობლობისთვის');
    }
}
