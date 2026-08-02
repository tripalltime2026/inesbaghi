<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blog_posts')) {
            return;
        }

        if (! Schema::hasColumn('blog_posts', 'source_url')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->text('source_url')->nullable()->after('published_at');
            });
        }

        if (! Schema::hasColumn('blog_posts', 'source_name')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->string('source_name', 120)->nullable()->after('source_url');
            });
        }

        if (! Schema::hasColumn('blog_posts', 'source_published_at')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->timestamp('source_published_at')->nullable()->after('source_name');
            });
        }

        if (! Schema::hasColumn('blog_posts', 'cover_encoding')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->string('cover_encoding', 20)->nullable()->after('cover_image');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('blog_posts')) {
            return;
        }

        $columns = collect(['source_url', 'source_name', 'source_published_at', 'cover_encoding'])
            ->filter(fn (string $column): bool => Schema::hasColumn('blog_posts', $column))
            ->values()
            ->all();

        if ($columns) {
            Schema::table('blog_posts', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
