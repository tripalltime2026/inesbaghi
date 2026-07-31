<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->change();
            $table->string('google_id', 255)->nullable()->unique()->after('email');
            $table->string('avatar_url', 2048)->nullable()->after('google_id');
            $table->timestamp('email_verified_at')->nullable()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar_url', 'email_verified_at']);
        });

        // Phone intentionally remains nullable: reverting it could fail for Google-only accounts.
    }
};
