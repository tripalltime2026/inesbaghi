<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBlogCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_latest_published_posts_as_clickable_article_cards(): void
    {
        $posts = collect(range(1, 4))->map(function (int $number): BlogPost {
            return BlogPost::create([
                'title' => "მთავარი გვერდის სტატია {$number}",
                'slug' => "home-article-{$number}",
                'body' => "სტატიის სრული ტექსტი {$number}",
                'status' => 'published',
                'published_at' => now()->subHours($number),
                'sort_order' => 0,
            ]);
        });

        BlogPost::create([
            'title' => 'დრაფტი მთავარ გვერდზე არ უნდა გამოჩნდეს',
            'slug' => 'home-draft',
            'body' => 'დრაფტის ტექსტი',
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        $response = $this->get('/')->assertOk();

        foreach ($posts->take(3) as $post) {
            $response
                ->assertSee($post->title)
                ->assertSee(route('public.blog.show', ['slug' => $post->slug]), false);
        }

        $response
            ->assertDontSee($posts->last()->title)
            ->assertDontSee('დრაფტი მთავარ გვერდზე არ უნდა გამოჩნდეს');
    }
}
