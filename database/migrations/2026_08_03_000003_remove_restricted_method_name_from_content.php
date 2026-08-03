<?php

use App\Services\RestrictedTerminology;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $terminology = new RestrictedTerminology();

        if (Schema::hasTable('site_content_entries')) {
            DB::table('site_content_entries')->updateOrInsert(
                ['key' => 'home.offer_method_title'],
                [
                    'section' => 'home',
                    'label' => 'მეთოდის ბარათის სათაური',
                    'value' => 'ბავშვზე ორიენტირებული სწავლება',
                    'input_type' => 'text',
                    'sort_order' => 8,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            DB::table('site_content_entries')->updateOrInsert(
                ['key' => 'methodology.intro'],
                [
                    'section' => 'methodology',
                    'label' => 'მეთოდოლოგიის აღწერა',
                    'value' => 'ჩვენ ვიყენებთ თამაშზე დაფუძნებულ, სენსორულ და პრაქტიკულ აქტივობებს. თითოეული ჯგუფის დღის რიტმი ბავშვის ბუნებრივ ციკლს მიჰყვება.',
                    'input_type' => 'textarea',
                    'sort_order' => 29,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        $this->sanitizeTable($terminology, 'site_content_entries', [
            'label', 'value',
        ]);
        $this->sanitizeTable($terminology, 'site_items', [
            'title', 'subtitle', 'body', 'badge', 'image_alt',
        ]);
        $this->sanitizeTable($terminology, 'blog_posts', [
            'title', 'excerpt', 'body', 'category', 'cover_alt',
        ]);
        $this->sanitizeTable($terminology, 'support_knowledge_articles', [
            'title', 'body', 'keywords',
        ]);
        $this->sanitizeTable($terminology, 'support_messages', [
            'body', 'message',
        ]);
        $this->sanitizeTable($terminology, 'forum_topics', [
            'title', 'body',
        ]);
        $this->sanitizeTable($terminology, 'forum_comments', [
            'body',
        ]);
    }

    public function down(): void
    {
        // The removed wording is intentionally not restored.
    }

    private function sanitizeTable(
        RestrictedTerminology $terminology,
        string $table,
        array $candidateColumns,
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $columns = array_values(array_filter(
            $candidateColumns,
            fn (string $column): bool => Schema::hasColumn($table, $column),
        ));

        if ($columns === []) {
            return;
        }

        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($terminology, $table, $columns): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column} ?? null;
                        if (! is_string($value) || $value === '') {
                            continue;
                        }

                        $sanitized = $terminology->sanitize($value);
                        if ($sanitized !== $value) {
                            $updates[$column] = $sanitized;
                        }
                    }

                    if ($updates !== []) {
                        if (Schema::hasColumn($table, 'updated_at')) {
                            $updates['updated_at'] = now();
                        }

                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
    }
};
