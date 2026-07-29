<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $items = [
            [
                'type' => 'club_post',
                'title' => 'საზაფხულო ზეიმის დეტალები',
                'subtitle' => 'ინეს ბაღი · დღეს, 09:30',
                'body' => 'ხუთშაბათს, 20 ივლისს, ეზოში გაიმართება საზაფხულო ზეიმი. მოსვლის დრო — 17:00. ველოდებით ყველას!',
                'badge' => 'კლუბის წევრები',
                'color' => '#A9D3C9',
                'meta' => json_encode(['art_label' => 'ბაღის ეზოში', 'likes' => 24, 'comments' => 8], JSON_UNESCAPED_UNICODE),
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'club_post',
                'title' => 'ახალი მასალები 3-4 წლის ჯგუფისთვის',
                'subtitle' => 'ინეს ბაღი · გუშინ, 16:45',
                'body' => 'დღეიდან ვიწყებთ ახალ სენსორულ თამაშებს — ბავშვები აღფრთოვანებულები არიან 🎨',
                'badge' => 'ჯგუფი: 3-4 წელი',
                'color' => '#EFE6A9',
                'meta' => json_encode(['art_label' => 'თამაშები ჯგუფში', 'likes' => 31, 'comments' => 12], JSON_UNESCAPED_UNICODE),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'club_event',
                'title' => 'საზაფხულო ზეიმი',
                'subtitle' => '20 ივლისი, 17:00 · ბაღის ეზო',
                'body' => 'ცეკვა, თამაშები და ტკბილეული ყველა ჯგუფისთვის.',
                'badge' => 'კლუბის წევრები',
                'color' => '#A9D3C9',
                'meta' => json_encode(['art_label' => 'ეზო', 'attendance' => '18 მოზრდილი · 22 ბავშვი დარეგისტრირდა'], JSON_UNESCAPED_UNICODE),
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'club_event',
                'title' => 'ღია გაკვეთილი',
                'subtitle' => '25 ივლისი, 11:00 · ჯგუფის ოთახი',
                'body' => 'აღმზრდელის დაკვირვება მშობლების თანდასწრებით.',
                'badge' => 'მხოლოდ ბაღის მშობლებისთვის',
                'color' => '#D3BDD3',
                'meta' => json_encode(['art_label' => 'ოთახი', 'attendance' => '9 მოზრდილი დარეგისტრირდა'], JSON_UNESCAPED_UNICODE),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'club_poll',
                'title' => 'რომელი დროა უფრო მოსახერხებელი შემდეგი შეხვედრისთვის?',
                'subtitle' => '18 ივლისამდე',
                'badge' => 'კლუბის წევრები',
                'color' => '#A9D3C9',
                'meta' => json_encode(['options' => [['label' => '17:00', 'percent' => 68], ['label' => '18:00', 'percent' => 22], ['label' => 'შაბათი დილით', 'percent' => 10]], 'votes' => 31], JSON_UNESCAPED_UNICODE),
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'club_poll',
                'title' => 'რომელი თემა გაინტერესებთ შემდეგ მშობელთა შეხვედრაზე?',
                'subtitle' => '25 ივლისამდე',
                'badge' => 'მხოლოდ ბაღის მშობლებისთვის',
                'color' => '#CCE8C4',
                'meta' => json_encode(['options' => [['label' => 'ემოციური განვითარება', 'percent' => 45], ['label' => 'კვება', 'percent' => 35], ['label' => 'სკოლისთვის მზადება', 'percent' => 20]], 'votes' => 20], JSON_UNESCAPED_UNICODE),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'club_topic',
                'title' => '📌 რა არის მშობელთა კლუბი?',
                'subtitle' => 'ნატო · 2 საათის წინ',
                'badge' => 'ზოგადი',
                'color' => '#A9D3C9',
                'meta' => json_encode(['comments' => 6], JSON_UNESCAPED_UNICODE),
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'club_topic',
                'title' => 'შემდეგი შეხვედრის დრო',
                'subtitle' => 'დავითი · გუშინ',
                'badge' => 'ზოგადი',
                'color' => '#EFE6A9',
                'meta' => json_encode(['comments' => 12], JSON_UNESCAPED_UNICODE),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($items as $item) {
            $exists = DB::table('site_items')->where('type', $item['type'])->where('title', $item['title'])->exists();
            if (! $exists) {
                DB::table('site_items')->insert($item);
            }
        }
    }

    public function down(): void
    {
        DB::table('site_items')->whereIn('type', ['club_post', 'club_event', 'club_poll', 'club_topic'])->delete();
    }
};
