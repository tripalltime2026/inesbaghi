<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->unsignedSmallInteger('birth_year')->nullable()->after('birth_date');
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            $table->foreignId('assigned_to_user_id')->nullable()->after('guardian_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('follow_up_at')->nullable()->after('preferred_tour_date')->index();
            $table->timestamp('tour_scheduled_at')->nullable()->after('follow_up_at')->index();
            $table->timestamp('status_updated_at')->nullable()->after('status');
            $table->foreignId('converted_child_id')->nullable()->after('source')->constrained('children')->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->after('converted_child_id');
        });

        Schema::create('admission_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
            $table->index(['admission_application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_notes');

        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropConstrainedForeignId('converted_child_id');
            $table->dropColumn(['follow_up_at', 'tour_scheduled_at', 'status_updated_at', 'converted_at']);
        });

        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn('birth_year');
        });
    }
};
