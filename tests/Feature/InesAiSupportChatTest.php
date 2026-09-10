<?php

namespace Tests\Feature;

use App\Models\KindergartenGroup;
use App\Models\SupportConversation;
use App\Models\SupportKnowledgeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InesAiSupportChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-30 20:00:00');

        config([
            'services.ines_ai.enabled' => false,
            'services.ines_ai.show_exact_availability' => false,
        ]);

        KindergartenGroup::create([
            'name' => '3–4 წლის ჯგუფი',
            'slug' => '3-4',
            'age_min_months' => 36,
            'age_max_months' => 47,
            'capacity' => 12,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        KindergartenGroup::create([
            'name' => '2–3 წლის ჯგუფი',
            'slug' => '2-3',
            'age_min_months' => 24,
            'age_max_months' => 35,
            'capacity' => 10,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_public_site_renders_ines_ai_widget(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ines AI')
            ->assertSee('ინეს ბაღის ციფრული ასისტენტი')
            ->assertSee('/css/ines-ai-chat.css?v=20260730', false)
            ->assertSee('/js/ines-ai-chat.js?v=20260730', false)
            ->assertSee('ადმინისტრატორთან დაკავშირება');
    }

    public function test_ines_ai_returns_clear_group_availability_message_without_follow_up_questions(): void
    {
        $token = $this->postJson('/support/chat/conversations')->json('conversation.token');

        foreach ([
            'არის ადგილი ჯგუფში?',
            '3-4 წლის ჯგუფი მაინტერესებს',
            'ჩარიცხვა მინდა',
        ] as $message) {
            $response = $this->postJson("/support/chat/conversations/{$token}/messages", [
                'body' => $message,
            ])->assertCreated();

            $latestBody = collect($response->json('conversation.messages'))->last()['body'];
            $this->assertSame(
                'ამ ეტაპზე ჯგუფებში ადგილები შევსებულია. ინფორმაციის გადასამოწმებლად დაგვიკავშირდით: 555 411 831.',
                $latestBody,
            );
        }
    }

    public function test_ines_ai_returns_exact_monthly_fee(): void
    {
        $token = $this->postJson('/support/chat/conversations')->json('conversation.token');

        foreach (['ფასი რამდენია?', 'ღირებულება', 'რამდენია თვეში?'] as $message) {
            $response = $this->postJson("/support/chat/conversations/{$token}/messages", [
                'body' => $message,
            ])->assertCreated();

            $latestBody = collect($response->json('conversation.messages'))->last()['body'];
            $this->assertSame('ინეს ბაღის ყოველთვიური ღირებულება 600 ლარია.', $latestBody);
        }
    }

    public function test_ines_ai_answers_minimum_age_and_kindergarten_identity_from_approved_knowledge(): void
    {
        $ageToken = $this->postJson('/support/chat/conversations')->json('conversation.token');

        $this->postJson("/support/chat/conversations/{$ageToken}/messages", [
            'body' => 'რომელი ასაკიდან არის მიღება?',
        ])->assertCreated()
            ->assertJsonFragment(['body' => 'მიღება იწყება 2 წლიდან. ამჟამად მოქმედი ასაკობრივი ჯგუფებია: 2-3 წლის, 3-4 წლის. მითხარით ბავშვის დაბადების წელი და სასწავლო წელი, რათა შესაბამისი ჯგუფი და ადგილი შევამოწმო.']);

        $identityToken = $this->postJson('/support/chat/conversations')->json('conversation.token');
        $response = $this->postJson("/support/chat/conversations/{$identityToken}/messages", [
            'body' => 'რით გამოირჩევა ინეს ბაღი?',
        ])->assertCreated();

        $latestBody = collect($response->json('conversation.messages'))->last()['body'];
        $this->assertStringContainsString('ბავშვზე ორიენტირებული', $latestBody);
        $this->assertStringContainsString('ეკომეგობრული', $latestBody);
    }

    public function test_human_handoff_is_visible_to_admin_and_admin_reply_returns_to_widget(): void
    {
        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'phone' => '+995555411831',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        $token = $this->postJson('/support/chat/conversations')->json('conversation.token');

        $this->postJson("/support/chat/conversations/{$token}/human")
            ->assertOk()
            ->assertJsonPath('conversation.status', 'waiting_admin');

        $conversation = SupportConversation::where('public_token', $token)->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/support')
            ->assertOk()
            ->assertSee('მხარდაჭერის ჩატები')
            ->assertSee('პასუხს ელოდება');

        $this->actingAs($admin)
            ->post(route('admin.support.messages.store', $conversation), [
                'body' => 'გამარჯობა, ადმინისტრაცია ჩაერთო საუბარში.',
            ])->assertRedirect();

        $this->getJson("/support/chat/conversations/{$token}")
            ->assertOk()
            ->assertJsonPath('conversation.status', 'in_progress')
            ->assertJsonFragment(['body' => 'გამარჯობა, ადმინისტრაცია ჩაერთო საუბარში.']);
    }

    public function test_admin_can_improve_ines_ai_approved_knowledge(): void
    {
        $admin = User::create([
            'name' => 'ადმინისტრატორი',
            'phone' => '+995555411832',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support.knowledge.store'), [
                'title' => 'ახალი პროგრამა',
                'category' => 'methodology',
                'content' => 'ეს არის ადმინისტრაციის მიერ დადასტურებული ახალი პროგრამის აღწერა.',
                'is_active' => true,
            ])->assertRedirect();

        $article = SupportKnowledgeArticle::where('title', 'ახალი პროგრამა')->firstOrFail();
        $this->assertTrue($article->is_active);
        $this->assertSame($admin->id, $article->updated_by_user_id);
    }
}
