<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_content_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('section')->index();
            $table->string('label');
            $table->longText('value')->nullable();
            $table->string('input_type')->default('text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('site_items', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('body')->nullable();
            $table->string('badge')->nullable();
            $table->string('color', 20)->nullable();
            $table->json('meta')->nullable();
            $table->binary('image')->nullable();
            $table->string('image_mime', 100)->nullable();
            $table->string('image_name')->nullable();
            $table->string('image_alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('category')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->binary('cover_image')->nullable();
            $table->string('cover_mime', 100)->nullable();
            $table->string('cover_name')->nullable();
            $table->string('cover_alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('site_items');
        Schema::dropIfExists('site_content_entries');
    }
};
