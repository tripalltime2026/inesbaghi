<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function generate(
        string $period,
        string $dueDate,
        ?int $groupId = null,
        ?int $actorUserId = null,
        ?string $ipAddress = null,
    ): array {
        $periodStart = CarbonImmutable::createFromFormat('Y-m-d', $period.'-01')->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();
        $dueAt = CarbonImmutable::parse($dueDate)->endOfDay();

        return DB::transaction(function () use ($period, $periodStart, $periodEnd, $dueAt, $groupId, $actorUserId, $ipAddress) {
            $enrollments = Enrollment::query()
                ->with('group')
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $periodEnd)
                ->where(function ($query) use ($periodStart) {
                    $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $periodStart);
                })
                ->when($groupId, fn ($query) => $query->where('kindergarten_group_id', $groupId))
                ->lockForUpdate()
                ->get();

            $created = 0;
            $skipped = 0;

            foreach ($enrollments as $enrollment) {
                $payment = Payment::firstOrCreate(
                    ['enrollment_id' => $enrollment->id, 'period' => $period],
                    [
                        'amount' => $enrollment->group?->monthly_fee ?? 0,
                        'discount_amount' => 0,
                        'paid_amount' => 0,
                        'currency' => 'GEL',
                        'status' => 'pending',
                        'due_at' => $dueAt,
                        'issued_by_user_id' => $actorUserId,
                    ],
                );

                $payment->wasRecentlyCreated ? $created++ : $skipped++;
            }

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actorUserId,
                'action' => 'billing.generated',
                'subject_type' => Payment::class,
                'subject_id' => null,
                'metadata' => json_encode([
                    'period' => $period,
                    'due_at' => $dueAt->toIso8601String(),
                    'group_id' => $groupId,
                    'created' => $created,
                    'skipped' => $skipped,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);

            return compact('created', 'skipped');
        });
    }
}
