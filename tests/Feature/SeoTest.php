<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['seo.site_url' => 'https://inesbaghi.ge']);
    }

    public function test_home_has_local_kindergarten_metadata_and_structured_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>საბავშვო ბაღი ბათუმში | ინეს ბაღი</title>', false)
            ->assertSee('<link rel="canonical" href="https://inesbaghi.ge/">', false)
            ->assertSee('property="og:title" content="საბავშვო ბაღი ბათუმში | ინეს ბაღი"', false)
            ->assertSee('"@type":["Preschool","LocalBusiness"]', false)
            ->assertSee('"addressLocality":"ბათუმი"', false)
            ->assertSee('aria-label="საჯარო გვერდები"', false)
            ->assertDontSee('name="keywords"', false)
            ->assertHeader('X-Robots-Tag', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
    }

    public function test_each_public_marketing_page_has_unique_indexable_seo(): void
    {
        foreach (config('seo.pages') as $key => $page) {
            if ($key === 'home') {
                continue;
            }

            $this->get($page['path'])
                ->assertOk()
                ->assertSee('<title>'.e($page['title']).'</title>', false)
                ->assertSee('<link rel="canonical" href="https://inesbaghi.ge'.$page['path'].'">', false)
                ->assertSee(e($page['h1']), false)
                ->assertSee('name="robots" content="index,follow', false)
                ->assertHeader('X-Robots-Tag', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
        }
    }

    public function test_faq_page_contains_visible_questions_and_faq_schema(): void
    {
        $this->get('/kitkhva-pasukhi')
            ->assertOk()
            ->assertSee('რომელი ასაკიდან იწყება მიღება?')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"Question"', false)
            ->assertSee('"@type":"Answer"', false);
    }

    public function test_sitemap_and_robots_expose_only_public_discovery_routes(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>https://inesbaghi.ge/</loc>', false)
            ->assertSee('<loc>https://inesbaghi.ge/chven-shesakheb</loc>', false)
            ->assertSee('<loc>https://inesbaghi.ge/charetskhva</loc>', false)
            ->assertDontSee('/admin/', false)
            ->assertDontSee('/parent/', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin/', false)
            ->assertSee('Disallow: /parent/', false)
            ->assertSee('Sitemap: https://inesbaghi.ge/sitemap.xml', false);
    }

    public function test_private_admin_pages_are_noindex(): void
    {
        $admin = User::create([
            'name' => 'SEO ადმინისტრატორი',
            'phone' => '+995555499999',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow,noarchive">', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
