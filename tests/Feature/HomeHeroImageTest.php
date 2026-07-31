<?php

namespace Tests\Feature;

use App\Models\SiteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HomeHeroImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_remove_the_home_hero_image(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/content')
            ->assertOk()
            ->assertSee('მთავარი სურათი')
            ->assertSee('home-hero-manager.css', false)
            ->assertSee('name="hero_image"', false)
            ->assertSee('name="image_alt"', false);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $image = UploadedFile::fake()->createWithContent('home-hero.png', $png);

        $this->put('/admin/content/hero', [
            'hero_image' => $image,
            'image_alt' => 'ბავშვები ინეს ბაღის ნათელ და ეკომეგობრულ სივრცეში',
        ])->assertRedirect()->assertSessionHas('success');

        $hero = SiteItem::query()->where('type', 'home_hero')->firstOrFail();

        $this->assertTrue($hero->is_active);
        $this->assertNotNull($hero->image);
        $this->assertSame('image/png', $hero->image_mime);
        $this->assertSame('home-hero.png', $hero->image_name);

        $this->get(route('content.home-hero'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get('/')
            ->assertOk()
            ->assertSee('/content/home-hero?v=', false)
            ->assertSee('ბავშვები ინეს ბაღის ნათელ და ეკომეგობრულ სივრცეში');

        $this->put('/admin/content/hero', [
            'remove_image' => 1,
            'image_alt' => 'ინეს ბაღის მთავარი ილუსტრაცია',
        ])->assertRedirect()->assertSessionHas('success');

        $hero->refresh();
        $this->assertFalse($hero->is_active);
        $this->assertNull($hero->image);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('/content/home-hero?v=', false)
            ->assertSee('images/ines-final-hero.svg', false);
    }

    public function test_non_admin_cannot_change_the_home_hero_image(): void
    {
        $this->put('/admin/content/hero', [
            'image_alt' => 'Unauthorized change',
        ])->assertRedirect('/shesvla');

        $this->assertDatabaseMissing('site_items', ['type' => 'home_hero']);
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
