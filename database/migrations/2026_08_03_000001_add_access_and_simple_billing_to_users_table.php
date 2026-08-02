<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('club_access_approved_at')->nullable();
            $table->unsignedBigInteger('club_access_approved_by_user_id')->nullable();
            $table->decimal('payment_due', 12, 2)->default(0);
            $table->decimal('payment_paid', 12, 2)->default(0);
            $table->date('payment_due_at')->nullable();
            $table->text('payment_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'club_access_approved_at',
                'club_access_approved_by_user_id',
                'payment_due',
                'payment_paid',
                'payment_due_at',
                'payment_note',
            ]);
        });
    }
};
