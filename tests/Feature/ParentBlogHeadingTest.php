<?php

namespace Tests\Feature;

use Tests\TestCase;

class ParentBlogHeadingTest extends TestCase
{
    public function test_parent_blog_uses_the_requested_heading(): void
    {
        $response = $this->get('/blogi');

        $response
            ->assertSuccessful()
            ->assertSee('ბლოგი მშობლებისთვის')
            ->assertDontSee('სასარგებლო რჩევები ყოველდღიური მშობლობისთვის');
    }
}
