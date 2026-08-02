<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_text_and_structured_public_content(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/content')
            ->assertOk()
            ->assertSee('პლატფორმის სრული მართვა')
            ->assertSee('კლუბის ლენტა')
            ->assertSee('სტატიები და ქავერები');

        $this->put('/admin/content/texts', [
            'content' => [
                'identity.hero_text' => 'ახალი მთავარი ტექსტი CMS-დან.',
                'contact.address' => 'ახალი მისამართი, ბათუმი',
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->get('/')
            ->assertOk()
            ->assertSee('ახალი მთავარი ტექსტი CMS-დან.')
            ->assertSee('ახალი მისამართი, ბათუმი')
            ->assertSee('js/cms-public.js', false);

        $this->post('/admin/content/items/faq', [
            'title' => 'შეიძლება თუ არა ონლაინ ვიზიტის დაჯავშნა?',
            'body' => 'დიახ, განაცხადის ფორმიდან მონიშნეთ ვიზიტის სურვილი.',
            'color' => '#A9D3C9',
            'meta_json' => '{}',
            'sort_order' => 50,
            'is_active' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('site_items', [
            'type' => 'faq',
            'title' => 'შეიძლება თუ არა ონლაინ ვიზიტის დაჯავშნა?',
            'is_active' => true,
        ]);

        $this->getJson('/content/public')
            ->assertOk()
            ->assertJsonFragment(['title' => 'შეიძლება თუ არა ონლაინ ვიზიტის დაჯავშნა?'])
            ->assertJsonStructure(['group', 'team', 'faq', 'gallery', 'club_post', 'club_event', 'club_poll', 'club_topic', 'blog']);
    }

    public function test_admin_can_upload_blog_cover_and_publish_post(): void
    {
        $this->loginAsAdmin();

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $cover = UploadedFile::fake()->createWithContent('cover.png', $png);

        $this->post('/admin/content/blog', [
            'title' => 'ახალი სტატია მშობლებისთვის',
            'excerpt' => 'მოკლე აღწერა.',
            'body' => 'სტატიის სრული ტექსტი.',
            'category' => 'განვითარება',
            'status' => 'published',
            'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'sort_order' => 0,
            'cover' => $cover,
            'cover_alt' => 'ბავშვები შემოქმედებით აქტივობაზე',
        ])->assertRedirect()->assertSessionHas('success');

        $post = BlogPost::where('title', 'ახალი სტატია მშობლებისთვის')->firstOrFail();
        $this->assertNotNull($post->cover_image);
        $this->assertSame('image/png', $post->cover_mime);

        $this->get(route('content.blog-cover', $post))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->getJson('/content/public')
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'ახალი სტატია მშობლებისთვის',
                'cover_alt' => 'ბავშვები შემოქმედებით აქტივობაზე',
            ]);
    }

    public function test_parent_portal_loads_managed_club_content_for_active_parent(): void
    {
        $this->postJson('/auth/demo/login', [
            'name' => 'დემო მშობელი',
            'phone' => '555123456',
            'privacy_accepted' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertOk()->assertJsonPath('user.role', 'member');

        $parent = User::where('phone', '+995555123456')->firstOrFail();
        $parent->update(['club_access_approved_at' => now()]);

        $group = KindergartenGroup::create([
            'name' => '3-4 წელი',
            'slug' => '3-4',
            'age_min_months' => 36,
            'age_max_months' => 47,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
        $child = Child::create(['first_name' => 'დემო', 'last_name' => 'ბავშვი', 'birth_year' => 2022]);
        $parent->children()->attach($child->id, ['relationship' => 'მშობელი', 'is_primary' => true, 'can_pick_up' => true]);
        Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => now()->startOfMonth(),
            'enrolled_at' => now(),
        ]);

        $this->actingAs($parent->fresh())
            ->get('/parent')
            ->assertOk()
            ->assertSee('js/cms-portal.js', false);

        $this->getJson('/content/public')
            ->assertOk()
            ->assertJsonFragment(['title' => 'საზაფხულო ზეიმის დეტალები'])
            ->assertJsonFragment(['title' => 'რომელი დროა უფრო მოსახერხებელი შემდეგი შეხვედრისთვის?']);
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
