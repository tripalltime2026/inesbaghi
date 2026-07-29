<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone', 20)->unique(); $table->string('email')->nullable()->unique();
            $table->string('role', 30)->default('member')->index(); $table->string('status', 30)->default('pending')->index();
            $table->timestamp('phone_verified_at')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); $table->foreignId('user_id')->nullable()->index(); $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable(); $table->longText('payload'); $table->integer('last_activity')->index();
        });
        Schema::create('cache', function (Blueprint $table) { $table->string('key')->primary(); $table->mediumText('value'); $table->integer('expiration'); });
        Schema::create('cache_locks', function (Blueprint $table) { $table->string('key')->primary(); $table->string('owner'); $table->integer('expiration'); });
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id(); $table->string('phone', 20)->index(); $table->string('code_hash'); $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index(); $table->timestamp('consumed_at')->nullable(); $table->string('request_ip', 45)->nullable(); $table->timestamps();
        });
        Schema::create('kindergarten_groups', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->unsignedSmallInteger('age_min_months');
            $table->unsignedSmallInteger('age_max_months'); $table->unsignedSmallInteger('capacity')->default(20); $table->decimal('monthly_fee', 10, 2)->default(600);
            $table->string('academic_year'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('children', function (Blueprint $table) {
            $table->id(); $table->string('first_name'); $table->string('last_name')->nullable(); $table->date('birth_date')->nullable();
            $table->text('medical_notes')->nullable(); $table->timestamp('photo_consent_at')->nullable(); $table->timestamps();
        });
        Schema::create('child_guardians', function (Blueprint $table) {
            $table->id(); $table->foreignId('child_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->default('parent'); $table->boolean('is_primary')->default(false); $table->boolean('can_pick_up')->default(true);
            $table->timestamps(); $table->unique(['child_id', 'user_id']);
        });
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id(); $table->foreignId('child_id')->constrained()->cascadeOnDelete(); $table->foreignId('kindergarten_group_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('active')->index(); $table->date('starts_on'); $table->date('ends_on')->nullable(); $table->timestamp('enrolled_at')->nullable(); $table->timestamps();
        });
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id(); $table->foreignId('guardian_user_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('parent_name');
            $table->string('phone', 20)->index(); $table->string('child_name'); $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('preferred_group', 10); $table->string('academic_year'); $table->boolean('wants_tour')->default(false);
            $table->date('preferred_tour_date')->nullable(); $table->text('comment')->nullable(); $table->string('status')->default('new')->index();
            $table->string('source')->default('website'); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete(); $table->string('period', 7); $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('GEL'); $table->string('status')->default('pending')->index(); $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable(); $table->string('provider_reference')->nullable()->index(); $table->timestamps(); $table->unique(['enrollment_id', 'period']);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('action')->index();
            $table->string('subject_type')->nullable(); $table->unsignedBigInteger('subject_id')->nullable(); $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable(); $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void
    {
        foreach (['audit_logs','payments','admission_applications','enrollments','child_guardians','children','kindergarten_groups','otp_codes','cache_locks','cache','sessions','users'] as $table) Schema::dropIfExists($table);
    }
};
