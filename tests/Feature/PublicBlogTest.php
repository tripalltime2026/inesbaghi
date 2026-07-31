<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_links_to_published_full_articles_only(): void
    {
        $published = BlogPost::create([
            'title' => 'სრული სატესტო სტატია',
            'slug' => 'sruli-satesto-statia',
            'excerpt' => 'სტატიის მოკლე აღწერა.',
            'body' => 'პირველი სრული აბზაცი.\n\nმეორე სრული აბზაცი.',
            'category' => 'რჩევები',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'sort_order' => 0,
        ]);

        BlogPost::create([
            'title' => 'დრაფტი არ უნდა გამოჩნდეს',
            'slug' => 'draft-post',
            'body' => 'დრაფტის ტექსტი',
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        BlogPost::create([
            'title' => 'მომავალი სტატია არ უნდა გამოჩნდეს',
            'slug' => 'future-post',
            'body' => 'მომავალი ტექსტი',
            'status' => 'published',
            'published_at' => now()->addDay(),
            'sort_order' => 0,
        ]);

        $this->get('/blogi')
            ->assertOk()
            ->assertSee('სრული სატესტო სტატია')
            ->assertSee(route('public.blog.show', ['slug' => $published->slug]), false)
            ->assertSee('სრულად წაკითხვა')
            ->assertDontSee('დრაფტი არ უნდა გამოჩნდეს')
            ->assertDontSee('მომავალი სტატია არ უნდა გამოჩნდეს');
    }

    public function test_published_article_has_full_body_seo_and_related_content(): void
    {
        $post = BlogPost::create([
            'title' => 'ბავშვის მშვიდი ადაპტაცია',
            'slug' => 'bavshvis-mshvidi-adaptatsia',
            'excerpt' => 'მშვიდი ადაპტაციის პრაქტიკული აღწერა.',
            'body' => "პირველი სრული აბზაცი ბავშვის ადაპტაციაზე.\n\nმეორე სრული აბზაცი მშობლის მხარდაჭერაზე.",
            'category' => 'აღზრდა',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'sort_order' => 0,
        ]);

        BlogPost::create([
            'title' => 'დაკავშირებული სტატია',
            'slug' => 'dakavshirebuli-statia',
            'excerpt' => 'დაკავშირებული სტატიის აღწერა.',
            'body' => 'დაკავშირებული სტატიის სრული ტექსტი.',
            'category' => 'აღზრდა',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'sort_order' => 1,
        ]);

        $canonical = rtrim((string) config('seo.site_url'), '/').'/blogi/'.$post->slug;

        $this->get('/blogi/'.$post->slug)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')
            ->assertSee('ბავშვის მშვიდი ადაპტაცია')
            ->assertSee('პირველი სრული აბზაცი ბავშვის ადაპტაციაზე.')
            ->assertSee('მეორე სრული აბზაცი მშობლის მხარდაჭერაზე.')
            ->assertSee('დაკავშირებული სტატია')
            ->assertSee('<link rel="canonical" href="'.$canonical.'">', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('გააზიარეთ სტატია');
    }

    public function test_draft_and_future_articles_are_not_publicly_readable(): void
    {
        BlogPost::create([
            'title' => 'დრაფტი',
            'slug' => 'private-draft',
            'body' => 'დრაფტის სრული ტექსტი',
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        BlogPost::create([
            'title' => 'მომავალი',
            'slug' => 'scheduled-future',
            'body' => 'მომავალი სტატიის ტექსტი',
            'status' => 'published',
            'published_at' => now()->addDay(),
            'sort_order' => 0,
        ]);

        $this->get('/blogi/private-draft')->assertNotFound();
        $this->get('/blogi/scheduled-future')->assertNotFound();
    }

    public function test_sitemap_contains_published_article_urls_only(): void
    {
        $published = BlogPost::create([
            'title' => 'ინდექსირებადი სტატია',
            'slug' => 'indexirebadi-statia',
            'body' => 'სრული ტექსტი',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'sort_order' => 0,
        ]);

        BlogPost::create([
            'title' => 'არაინდექსირებადი დრაფტი',
            'slug' => 'araindexirebadi-draft',
            'body' => 'დრაფტის ტექსტი',
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/blogi/'.$published->slug, false)
            ->assertDontSee('/blogi/araindexirebadi-draft', false);
    }
}
