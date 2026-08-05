<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ClubPoll;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupClubFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_poll_is_visible_and_notified_only_inside_selected_age_group(): void
    {
        $group23 = $this->group('2-3', '2-3 წელი', 24);
        $group45 = $this->group('4-5', '4-5 წელი', 48);
        $parent23 = $this->parentInGroup('555410001', 'თამარი', $group23);
        $parent45 = $this->parentInGroup('555410002', 'ნინო', $group45);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.club.polls.store'), [
            'kindergarten_group_id' => $group23->id,
            'question' => 'რომელი დღეა უკეთესი მშობელთა შეხვედრისთვის?',
            'description' => 'აირჩიეთ თქვენთვის მოსახერხებელი დღე.',
            'status' => 'published',
            'options' => ['ორშაბათი', 'ოთხშაბათი', 'პარასკევი'],
        ])->assertRedirect()->assertSessionHas('success');

        $poll = ClubPoll::firstOrFail();
        $this->assertSame($group23->id, $poll->kindergarten_group_id);
        $this->assertCount(3, $poll->options);

        $this->assertDatabaseHas('club_notifications', [
            'user_id' => $parent23->id,
            'type' => 'poll_published',
        ]);
        $this->assertDatabaseMissing('club_notifications', [
            'user_id' => $parent45->id,
            'type' => 'poll_published',
        ]);

        $this->actingAs($parent23)
            ->getJson(route('parent.forum.index', ['group_id' => $group23->id]))
            ->assertOk()
            ->assertJsonPath('active_group.id', $group23->id)
            ->assertJsonPath('polls.0.question', 'რომელი დღეა უკეთესი მშობელთა შეხვედრისთვის?');

        $this->actingAs($parent45)
            ->getJson(route('parent.forum.index', ['group_id' => $group45->id]))
            ->assertOk()
            ->assertJsonCount(0, 'polls');

        $this->actingAs($parent45)
            ->getJson(route('parent.forum.index', ['group_id' => $group23->id]))
            ->assertNotFound();
    }

    public function test_parent_can_vote_once_change_vote_and_cannot_vote_in_other_group(): void
    {
        $group23 = $this->group('2-3', '2-3 წელი', 24);
        $group45 = $this->group('4-5', '4-5 წელი', 48);
        $parent23 = $this->parentInGroup('555410003', 'მარიამი', $group23);
        $parent45 = $this->parentInGroup('555410004', 'ანა', $group45);
        $admin = $this->admin();

        $poll = ClubPoll::create([
            'kindergarten_group_id' => $group23->id,
            'created_by_user_id' => $admin->id,
            'question' => 'რომელი აქტივობა ავირჩიოთ?',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $first = $poll->options()->create(['label' => 'ხატვა', 'position' => 1]);
        $second = $poll->options()->create(['label' => 'ცეკვა', 'position' => 2]);

        $this->actingAs($parent23)
            ->postJson(route('parent.polls.vote', $poll), ['option_id' => $first->id])
            ->assertOk();

        $this->actingAs($parent23)
            ->postJson(route('parent.polls.vote', $poll), ['option_id' => $second->id])
            ->assertOk();

        $this->assertDatabaseCount('club_poll_votes', 1);
        $this->assertDatabaseHas('club_poll_votes', [
            'club_poll_id' => $poll->id,
            'club_poll_option_id' => $second->id,
            'user_id' => $parent23->id,
        ]);

        $this->actingAs($parent45)
            ->postJson(route('parent.polls.vote', $poll), ['option_id' => $first->id])
            ->assertNotFound();
    }

    public function test_forum_feed_returns_only_questions_from_the_active_parent_group(): void
    {
        $group23 = $this->group('2-3', '2-3 წელი', 24);
        $group45 = $this->group('4-5', '4-5 წელი', 48);
        $parent23 = $this->parentInGroup('555410005', 'ეკა', $group23);
        $parent45 = $this->parentInGroup('555410006', 'სალომე', $group45);

        ForumTopic::create([
            'kindergarten_group_id' => $group23->id,
            'user_id' => $parent23->id,
            'category' => 'general',
            'title' => '2-3 ჯგუფის კითხვა',
            'body' => 'ეს კითხვა მხოლოდ 2-3 ჯგუფში უნდა გამოჩნდეს.',
            'status' => 'open',
            'priority' => 'normal',
            'last_activity_at' => now(),
        ]);

        ForumTopic::create([
            'kindergarten_group_id' => $group45->id,
            'user_id' => $parent45->id,
            'category' => 'general',
            'title' => '4-5 ჯგუფის კითხვა',
            'body' => 'ეს კითხვა მხოლოდ 4-5 ჯგუფში უნდა გამოჩნდეს.',
            'status' => 'open',
            'priority' => 'normal',
            'last_activity_at' => now(),
        ]);

        $response = $this->actingAs($parent23)
            ->getJson(route('parent.forum.index', ['group_id' => $group23->id]))
            ->assertOk()
            ->assertJsonCount(1, 'topics')
            ->assertJsonPath('topics.0.title', '2-3 ჯგუფის კითხვა');

        $this->assertStringNotContainsString('4-5 ჯგუფის კითხვა', $response->getContent());
    }

    public function test_admin_poll_page_is_available_with_simple_form(): void
    {
        $this->group('3-4', '3-4 წელი', 36);

        $this->actingAs($this->admin())
            ->get(route('admin.club.polls.index'))
            ->assertOk()
            ->assertSee('ჯგუფის გამოკითხვები')
            ->assertSee('ერთი გამოკითხვა — ერთი ასაკობრივი ჯგუფი')
            ->assertSee('options[]', false);
    }

    private function group(string $slug, string $name, int $minimumAge): KindergartenGroup
    {
        return KindergartenGroup::create([
            'name' => $name,
            'slug' => $slug,
            'age_min_months' => $minimumAge,
            'age_max_months' => $minimumAge + 11,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }

    private function parentInGroup(string $phone, string $name, KindergartenGroup $group): User
    {
        $parent = User::create([
            'name' => $name,
            'username' => strtolower($name).$phone,
            'phone' => '+995'.$phone,
            'role' => 'parent',
            'status' => 'active',
            'phone_verified_at' => now(),
            'club_access_approved_at' => now(),
        ]);

        $child = Child::create([
            'first_name' => 'ბავშვი',
            'last_name' => $name,
            'birth_year' => 2022,
            'birth_date' => '2022-01-01',
        ]);

        $parent->children()->attach($child->id, [
            'relationship' => 'მშობელი',
            'is_primary' => true,
            'can_pick_up' => true,
        ]);

        Enrollment::create([
            'child_id' => $child->id,
            'kindergarten_group_id' => $group->id,
            'status' => 'active',
            'starts_on' => now()->startOfMonth(),
            'enrolled_at' => now(),
        ]);

        return $parent;
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'ადმინისტრატორი',
            'username' => 'group-feed-admin-'.User::query()->count(),
            'phone' => '+99555542'.str_pad((string) User::query()->count(), 4, '0', STR_PAD_LEFT),
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }
}
