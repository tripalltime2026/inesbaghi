<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlogPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_a_post_with_a_postgresql_safe_cover(): void
    {
        $this->loginAsAdmin();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $cover = UploadedFile::fake()->createWithContent('article.png', $png);

        $this->post('/admin/content/blog', [
            'title' => 'ტესტ სტატია',
            'excerpt' => 'მოკლე აღწერა',
            'body' => 'სრული ტექსტი',
            'category' => 'რჩევები',
            'status' => 'published',
            'cover' => $cover,
            'cover_alt' => 'ტესტ სტატიის ქავერი',
        ])->assertRedirect()->assertSessionHas('success');

        $post = BlogPost::query()->where('title', 'ტესტ სტატია')->firstOrFail();

        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame('base64', $post->cover_encoding);
        $this->assertSame($png, base64_decode((string) $post->cover_image, true));

        $this->get(route('content.blog-cover', $post))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertContent($png);

        $this->get(route('public.blog.show', ['slug' => $post->slug]))
            ->assertOk()
            ->assertSee('ტესტ სტატია');
    }

    public function test_admin_can_import_a_marketer_article_as_a_draft(): void
    {
        $this->loginAsAdmin();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $url = 'https://www.marketer.ge/ines-bagi-batumshi/';
        $imageUrl = 'https://www.marketer.ge/uploads/ines-cover.png';

        Http::fake([
            $url => Http::response(<<<HTML
                <!doctype html><html><head>
                <meta property="og:title" content="ინეს ბაღი — ინოვაციური სასწავლო გარემო">
                <meta property="og:description" content="ინეს ბაღი 2020 წელს გამოჩნდა ბათუმში და ბავშვებისთვის ინოვაციურ გარემოს ქმნის.">
                <meta property="og:site_name" content="Marketer.ge">
                <meta property="og:url" content="{$url}">
                <meta property="og:image" content="{$imageUrl}">
                <meta property="article:published_time" content="2023-07-03T19:00:00+04:00">
                </head><body><article><p>სტატიის ტექსტი.</p></article></body></html>
                HTML, 200, ['Content-Type' => 'text/html; charset=UTF-8']),
            $imageUrl => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $this->post(route('admin.content.blog.import'), [
            'source_url' => $url,
        ])->assertRedirect()->assertSessionHas('success');

        $post = BlogPost::query()->where('source_url', $url)->firstOrFail();

        $this->assertSame('draft', $post->status);
        $this->assertSame('Marketer.ge', $post->source_name);
        $this->assertSame('მედია', $post->category);
        $this->assertSame('base64', $post->cover_encoding);
        $this->assertNotNull($post->source_published_at);
    }

    public function test_legacy_raw_blog_cover_is_still_served(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $post = BlogPost::create([
            'title' => 'Legacy cover',
            'slug' => 'legacy-cover',
            'status' => 'published',
            'published_at' => now(),
            'cover_image' => $png,
            'cover_mime' => 'image/png',
            'cover_name' => 'legacy.png',
        ]);

        $this->get(route('content.blog-cover', $post))
            ->assertOk()
            ->assertContent($png);
    }

    private function loginAsAdmin(): void
    {
        config()->set('services.demo_auth.enabled', true);
        config()->set('services.demo_auth.admin_phone', '555411831');

        $this->postJson('/auth/demo/login', [
            'name' => 'ინეს ბაღის ადმინისტრატორი',
            'phone' => '555411831',
        ])->assertOk()->assertJsonPath('user.role', 'admin');
    }
}
