<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id(); $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kindergarten_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visibility')->default('members'); $table->string('title'); $table->text('body');
            $table->timestamp('published_at')->nullable()->index(); $table->timestamps();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id(); $table->foreignId('kindergarten_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visibility')->default('members'); $table->string('title'); $table->text('description')->nullable();
            $table->string('location')->nullable(); $table->timestamp('starts_at'); $table->timestamp('ends_at')->nullable(); $table->timestamps();
        });
        Schema::create('photo_albums', function (Blueprint $table) {
            $table->id(); $table->foreignId('kindergarten_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $table->string('title');
            $table->string('visibility')->default('group'); $table->timestamp('published_at')->nullable(); $table->timestamps();
        });
        Schema::create('photos', function (Blueprint $table) {
            $table->id(); $table->foreignId('photo_album_id')->constrained()->cascadeOnDelete(); $table->string('disk')->default('private');
            $table->string('path'); $table->string('mime_type'); $table->unsignedBigInteger('size_bytes'); $table->string('checksum', 64)->nullable(); $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('photos'); Schema::dropIfExists('photo_albums'); Schema::dropIfExists('events'); Schema::dropIfExists('posts');
    }
};
