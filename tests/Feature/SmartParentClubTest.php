<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ClubEvent;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartParentClubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_publishes_group_event_and_parent_can_respond(): void
    {
        $groupA = $this->group('3-4', '3-4 წელი', 36);
        $groupB = $this->group('4-5', '4-5 წელი', 48);
        $parent = $this->parentInGroup('555300001', 'თამარი', $groupA);
        $otherParent = $this->parentInGroup('555300002', 'მარიამი', $groupB);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.club.events.store'), [
            'kindergarten_group_id' => $groupA->id,
            'title' => 'ოჯახური სპორტული დღე',
            'description' => 'ბავშვები და მშობლები ერთად მიიღებენ მონაწილეობას სპორტულ აქტივობებში.',
            'location' => 'ინეს ბაღის ეზო',
            'starts_at' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(4)->addHours(2)->format('Y-m-d H:i:s'),
            'capacity' => 30,
            'status' => 'published',
            'is_featured' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $event = ClubEvent::firstOrFail();
        $this->assertDatabaseHas('club_notifications', [
            'user_id' => $parent->id,
            'type' => 'event_published',
        ]);
        $this->assertDatabaseMissing('club_notifications', [
            'user_id' => $otherParent->id,
            'type' => 'event_published',
        ]);

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('ოჯახური სპორტული დღე')
            ->assertSee('მომავალი ღონისძიებები');

        $this->actingAs($parent)->post(route('parent.events.response', $event), [
            'status' => 'going',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('club_event_responses', [
            'club_event_id' => $event->id,
            'user_id' => $parent->id,
            'status' => 'going',
        ]);

        $this->actingAs($otherParent)->post(route('parent.events.response', $event), [
            'status' => 'going',
        ])->assertNotFound();
    }

    public function test_admin_official_answer_updates_question_and_notifies_parent(): void
    {
        $group = $this->group('3-4', '3-4 წელი', 36);
        $parent = $this->parentInGroup('555300003', 'ნინო', $group);
        $admin = $this->admin();

        $this->actingAs($parent)->postJson(route('parent.forum.topics.store'), [
            'kindergarten_group_id' => $group->id,
            'category' => 'kindergarten',
            'title' => 'როდის იწყება ზაფხულის პროგრამა?',
            'body' => 'გთხოვთ გვაცნობოთ ზაფხულის პროგრამის დაწყების თარიღი.',
        ])->assertCreated();

        $topic = ForumTopic::firstOrFail();
        $this->assertSame('open', $topic->status);

        $this->actingAs($admin)->post(route('admin.club.topics.reply', $topic), [
            'body' => 'ზაფხულის პროგრამა 15 ივნისს დაიწყება. დეტალური განრიგი პირად კაბინეტში გამოქვეყნდება.',
        ])->assertRedirect()->assertSessionHas('success');

        $topic->refresh();
        $this->assertSame('answered', $topic->status);
        $this->assertSame($admin->id, $topic->answered_by_user_id);
        $this->assertNotNull($topic->answered_at);

        $this->assertDatabaseHas('forum_comments', [
            'forum_topic_id' => $topic->id,
            'user_id' => $admin->id,
            'is_official_answer' => true,
        ]);
        $this->assertDatabaseHas('club_notifications', [
            'user_id' => $parent->id,
            'type' => 'official_answer',
        ]);

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('როდის იწყება ზაფხულის პროგრამა?')
            ->assertSee('ზაფხულის პროგრამა 15 ივნისს დაიწყება')
            ->assertSee('პასუხგაცემული');
    }

    public function test_parent_notification_preferences_control_event_alerts(): void
    {
        $group = $this->group('5-6', '5-6 წელი', 60);
        $parent = $this->parentInGroup('555300004', 'ეკა', $group);
        $admin = $this->admin();

        $this->actingAs($parent)->patch(route('parent.preferences.update'), [
            'event_updates' => false,
            'forum_replies' => true,
            'payment_reminders' => true,
            'weekly_digest' => false,
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.club.events.store'), [
            'kindergarten_group_id' => $group->id,
            'title' => 'მშობელთა შეხვედრა',
            'description' => 'შეხვედრა ახალი სასწავლო წლის გეგმების განსახილველად.',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'status' => 'published',
            'is_featured' => '0',
        ])->assertRedirect();

        $this->assertDatabaseMissing('club_notifications', [
            'user_id' => $parent->id,
            'type' => 'event_published',
        ]);
    }

    public function test_admin_club_center_shows_questions_and_metrics(): void
    {
        $group = $this->group('2-3', '2-3 წელი', 24);
        $parent = $this->parentInGroup('555300005', 'ანა', $group);
        $admin = $this->admin();

        ForumTopic::create([
            'kindergarten_group_id' => $group->id,
            'user_id' => $parent->id,
            'category' => 'nutrition',
            'title' => 'კვების მენიუს შესახებ',
            'body' => 'შესაძლებელია თუ არა მიმდინარე კვირის მენიუს ნახვა?',
            'status' => 'open',
            'priority' => 'normal',
            'last_activity_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.club.index'))
            ->assertOk()
            ->assertSee('მშობელთა კლუბის მართვა')
            ->assertSee('კვების მენიუს შესახებ')
            ->assertSee('უპასუხო კითხვები')
            ->assertSee('ახალი ღონისძიება');
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
            'username' => 'smart-club-admin',
            'phone' => '+995555399999',
            'role' => 'admin',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }
}
