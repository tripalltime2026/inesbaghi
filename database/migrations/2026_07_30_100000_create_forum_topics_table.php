<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kindergarten_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 80)->default('general');
            $table->string('title', 180);
            $table->text('body');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->index(['kindergarten_group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_topics');
    }
};
