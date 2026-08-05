<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_topics', function (Blueprint $table): void {
            $table->string('status', 30)->default('open');
            $table->string('priority', 30)->default('normal');
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('answered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->index(['status', 'last_activity_at']);
            $table->index(['kindergarten_group_id', 'is_pinned', 'last_activity_at'], 'forum_topics_group_activity_index');
        });

        Schema::table('forum_comments', function (Blueprint $table): void {
            $table->boolean('is_official_answer')->default(false);
            $table->timestamp('edited_at')->nullable();
        });

        Schema::create('club_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kindergarten_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description');
            $table->string('location', 180)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status', 30)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['kindergarten_group_id', 'starts_at']);
        });

        Schema::create('club_event_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('going');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['club_event_id', 'user_id']);
            $table->index(['club_event_id', 'status']);
        });

        Schema::create('club_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('title', 180);
            $table->text('body')->nullable();
            $table->string('action_url', 500)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at'], 'club_notifications_user_read_index');
        });

        Schema::create('club_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('event_updates')->default(true);
            $table->boolean('forum_replies')->default(true);
            $table->boolean('payment_reminders')->default(true);
            $table->boolean('weekly_digest')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_notification_preferences');
        Schema::dropIfExists('club_notifications');
        Schema::dropIfExists('club_event_responses');
        Schema::dropIfExists('club_events');

        Schema::table('forum_comments', function (Blueprint $table): void {
            $table->dropColumn(['is_official_answer', 'edited_at']);
        });

        Schema::table('forum_topics', function (Blueprint $table): void {
            $table->dropForeign(['answered_by_user_id']);
            $table->dropIndex(['status', 'last_activity_at']);
            $table->dropIndex('forum_topics_group_activity_index');
            $table->dropColumn([
                'status',
                'priority',
                'is_pinned',
                'answered_by_user_id',
                'answered_at',
                'last_activity_at',
            ]);
        });
    }
};
