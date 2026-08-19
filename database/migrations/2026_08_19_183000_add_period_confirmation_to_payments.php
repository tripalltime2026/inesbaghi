<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->date('period_starts_on')->nullable()->after('period');
            $table->date('period_ends_on')->nullable()->after('period_starts_on');
            $table->timestamp('confirmed_at')->nullable()->index()->after('issued_by_user_id');
            $table->foreignId('confirmed_by_user_id')
                ->nullable()
                ->after('confirmed_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('payments')
            ->orderBy('id')
            ->chunkById(200, function ($payments): void {
                foreach ($payments as $payment) {
                    $periodStart = CarbonImmutable::createFromFormat('Y-m-d', $payment->period.'-01')->startOfMonth();
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update([
                            'period_starts_on' => $periodStart->toDateString(),
                            'period_ends_on' => $periodStart->endOfMonth()->toDateString(),
                            'confirmed_at' => $payment->created_at ?? now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['confirmed_by_user_id']);
            $table->dropColumn([
                'period_starts_on',
                'period_ends_on',
                'confirmed_at',
                'confirmed_by_user_id',
            ]);
        });
    }
};
