<?php

namespace Tests\Unit;

use App\Models\KindergartenGroup;
use App\Services\ParentClubContent;
use PHPUnit\Framework\TestCase;

class ParentClubContentTest extends TestCase
{
    public function test_it_keeps_global_items_and_only_the_selected_groups_items(): void
    {
        $twoThree = new KindergartenGroup(['name' => '2-3 წელი', 'slug' => '2-3']);
        $twoThree->setAttribute('id', 1);

        $threeFour = new KindergartenGroup(['name' => '3-4 წელი', 'slug' => '3-4']);
        $threeFour->setAttribute('id', 2);

        $payload = [
            'club_post' => [
                ['title' => 'ყველასთვის', 'badge' => 'კლუბის წევრები', 'meta' => []],
                ['title' => '2-3 ჯგუფი', 'badge' => 'ჯგუფი: 2-3 წელი', 'meta' => []],
                ['title' => '3-4 ჯგუფი', 'badge' => 'ჯგუფი: 3-4 წელი', 'meta' => []],
                ['title' => 'Explicit', 'badge' => '', 'meta' => ['audience' => 'group', 'kindergarten_group_id' => 2]],
            ],
        ];

        $result = (new ParentClubContent())->forGroup(
            $payload,
            $threeFour,
            collect([$twoThree, $threeFour]),
        );

        $this->assertSame(
            ['ყველასთვის', '3-4 ჯგუფი', 'Explicit'],
            array_column($result['club_post'], 'title'),
        );
        $this->assertSame('ყველა ჯგუფი', $result['club_post'][0]['visibility_label']);
        $this->assertSame('3-4 წელი', $result['club_post'][1]['visibility_label']);
    }

    public function test_group_audience_without_a_valid_group_is_hidden(): void
    {
        $group = new KindergartenGroup(['name' => '4-5 წელი', 'slug' => '4-5']);
        $group->setAttribute('id', 3);

        $service = new ParentClubContent();

        $this->assertFalse($service->isVisibleToGroup(
            ['title' => 'არასწორი სამიზნე', 'meta' => ['audience' => 'group']],
            $group,
            collect([$group]),
        ));
    }

    public function test_unknown_group_badge_never_becomes_global_content(): void
    {
        $group = new KindergartenGroup(['name' => '5-6 წელი', 'slug' => '5-6']);
        $group->setAttribute('id', 4);

        $service = new ParentClubContent();

        $this->assertFalse($service->isVisibleToGroup(
            ['title' => 'ძველი ჯგუფის სიახლე', 'badge' => 'ჯგუფი: 1-2 წელი', 'meta' => []],
            $group,
            collect([$group]),
        ));
    }
}
