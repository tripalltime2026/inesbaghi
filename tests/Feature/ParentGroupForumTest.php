<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\KindergartenGroup;
use App\Models\User;
use App\Support\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentGroupForumTest extends TestCase
{
    use RefreshDatabase;

    public function test_topics_are_visible_only_inside_the_child_group_and_members_can_comment(): void
    {
        $groupA = $this->group('3-4', '3-4 წელი', 36);
        $groupB = $this->group('4-5', '4-5 წელი', 48);

        $author = $this->parentInGroup('555100001', 'ანა', $groupA);
        $sameGroupParent = $this->parentInGroup('555100002', 'ნინო', $groupA);
        $otherGroupParent = $this->parentInGroup('555100003', 'მარიამი', $groupB);

        $createResponse = $this->actingAs($author)->postJson('/parent/forum/topics', [
            'kindergarten_group_id' => $groupA->id,
            'category' => 'general',
            'title' => 'შაბათის შეხვედრა ბავშვებისთვის',
            'body' => 'შევხვდეთ ეზოში და ბავშვებმა ერთად ითამაშონ.',
        ]);

        $createResponse->assertCreated()->assertJsonPath('ok', true);
        $topic = ForumTopic::firstOrFail();

        $this->actingAs($sameGroupParent)
            ->getJson('/parent/forum/data')
            ->assertOk()
            ->assertJsonFragment(['title' => 'შაბათის შეხვედრა ბავშვებისთვის'])
            ->assertJsonFragment(['group_name' => '3-4 წელი']);

        $this->actingAs($sameGroupParent)
            ->postJson('/parent/forum/topics/'.$topic->id.'/comments', ['body' => 'ჩვენც მოვალთ!'])
            ->assertCreated()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('forum_comments', [
            'forum_topic_id' => $topic->id,
            'user_id' => $sameGroupParent->id,
            'body' => 'ჩვენც მოვალთ!',
        ]);

        $this->actingAs($otherGroupParent)
            ->getJson('/parent/forum/data')
            ->assertOk()
            ->assertJsonMissing(['title' => 'შაბათის შეხვედრა ბავშვებისთვის']);

        $this->actingAs($otherGroupParent)
            ->postJson('/parent/forum/topics/'.$topic->id.'/comments', ['body' => 'ეს თემა არ უნდა ჩანდეს.'])
            ->assertNotFound();
    }

    public function test_parent_cannot_create_a_topic_for_an_unrelated_group(): void
    {
        $ownGroup = $this->group('3-4', '3-4 წელი', 36);
        $foreignGroup = $this->group('5-6', '5-6 წელი', 60);
        $parent = $this->parentInGroup('555100004', 'თამარი', $ownGroup);

        $this->actingAs($parent)->postJson('/parent/forum/topics', [
            'kindergarten_group_id' => $foreignGroup->id,
            'category' => 'general',
            'title' => 'უცხო ჯგუფის თემა',
            'body' => 'ამ თემის შექმნა არ უნდა იყოს შესაძლებელი.',
        ])->assertUnprocessable()->assertJsonValidationErrors('kindergarten_group_id');

        $this->assertDatabaseCount('forum_topics', 0);
    }

    public function test_registered_member_does_not_receive_fake_group_or_forum_access(): void
    {
        config([
            'services.demo_auth.enabled' => true,
            'services.demo_auth.admin_phone' => '555411831',
        ]);

        $this->postJson('/auth/demo/login', [
            'name' => 'დემო მომხმარებელი',
            'phone' => '555222333',
            'privacy_accepted' => true,
            'marketing_consent' => false,
            'privacy_policy_version' => PrivacyPolicy::VERSION,
        ])->assertOk()
            ->assertJsonPath('user.role', 'member')
            ->assertJsonPath('user.parent_club_access', false)
            ->assertJsonPath('redirect_to', route('account.status'));

        $this->assertDatabaseCount('kindergarten_groups', 0);
        $this->assertDatabaseCount('children', 0);
        $this->assertDatabaseCount('enrollments', 0);

        $this->get('/parent')->assertRedirect(route('account.status'));
        $this->get('/account')
            ->assertOk()
            ->assertSee('მხოლოდ რეგისტრაცია საკმარისი არ არის');
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
            'phone' => '+995'.$phone,
            'role' => 'parent',
            'status' => 'active',
            'phone_verified_at' => now(),
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
}
