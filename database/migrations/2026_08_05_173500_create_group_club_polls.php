<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_polls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kindergarten_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('question', 240);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['kindergarten_group_id', 'status', 'published_at'], 'club_polls_group_status_index');
        });

        Schema::create('club_poll_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_poll_id')->constrained()->cascadeOnDelete();
            $table->string('label', 180);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->index(['club_poll_id', 'position']);
        });

        Schema::create('club_poll_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['club_poll_id', 'user_id']);
            $table->index(['club_poll_option_id', 'club_poll_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_poll_votes');
        Schema::dropIfExists('club_poll_options');
        Schema::dropIfExists('club_polls');
    }
};
