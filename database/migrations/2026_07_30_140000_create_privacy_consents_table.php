<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('consent_type', 80);
            $table->string('policy_version', 32);
            $table->string('legal_basis', 80);
            $table->char('consent_text_hash', 64);
            $table->timestamp('accepted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_type', 'policy_version']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['consent_type', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_consents');
    }
};
