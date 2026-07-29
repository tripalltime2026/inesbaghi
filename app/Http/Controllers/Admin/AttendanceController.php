<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'group_id' => ['nullable', 'integer', 'exists:kindergarten_groups,id'],
        ]);

        $date = CarbonImmutable::parse($filters['date'] ?? today())->toDateString();
        $groups = KindergartenGroup::query()->where('is_active', true)->orderBy('name')->get();
        $selectedGroup = isset($filters['group_id'])
            ? $groups->firstWhere('id', (int) $filters['group_id'])
            : $groups->first();

        $enrollments = collect();
        if ($selectedGroup) {
            $enrollments = Enrollment::query()
                ->with([
                    'child.guardians',
                    'child.attendanceRecords' => fn ($query) => $query->whereDate('attendance_date', $date),
                ])
                ->where('kindergarten_group_id', $selectedGroup->id)
                ->where('status', 'active')
                ->whereDate('starts_on', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date);
                })
                ->get()
                ->sortBy(fn ($enrollment) => $enrollment->child->first_name.' '.$enrollment->child->last_name)
                ->values();
        }

        $counts = [
            'total' => $enrollments->count(),
            'present' => $enrollments->filter(fn ($enrollment) => $enrollment->child->attendanceRecords->first()?->status === 'present')->count(),
            'absent' => $enrollments->filter(fn ($enrollment) => in_array($enrollment->child->attendanceRecords->first()?->status, ['absent', 'excused', 'sick'], true))->count(),
            'not_recorded' => $enrollments->filter(fn ($enrollment) => ! $enrollment->child->attendanceRecords->first())->count(),
        ];

        return view('admin.attendance.index', compact('groups', 'selectedGroup', 'enrollments', 'date', 'counts'));
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'group_id' => ['required', 'integer', 'exists:kindergarten_groups,id'],
            'action' => ['nullable', Rule::in(['save', 'check_in', 'check_out'])],
            'status' => ['nullable', Rule::in(array_keys(AttendanceRecord::STATUSES))],
            'checked_in_time' => ['nullable', 'date_format:H:i'],
            'checked_out_time' => ['nullable', 'date_format:H:i'],
            'pickup_by_name' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $date = CarbonImmutable::parse($validated['date'])->toDateString();
        $this->assertActiveEnrollment($child, (int) $validated['group_id'], $date);
        $action = $validated['action'] ?? 'save';

        DB::transaction(function () use ($request, $child, $validated, $date, $action) {
            $record = AttendanceRecord::query()->lockForUpdate()->firstOrNew([
                'child_id' => $child->id,
                'attendance_date' => $date,
            ]);

            $record->kindergarten_group_id = $validated['group_id'];
            $record->recorded_by_user_id = $request->user()->id;

            if ($action === 'check_in') {
                $record->status = 'present';
                $record->checked_in_at ??= $this->actionTimestamp($date, '09:00');
            } elseif ($action === 'check_out') {
                if (! $record->checked_in_at) {
                    throw ValidationException::withMessages(['action' => 'წასვლამდე ბავშვის მოსვლა უნდა იყოს დაფიქსირებული.']);
                }
                $record->checked_out_at = $this->actionTimestamp($date, '17:00');
                $record->pickup_by_name = $validated['pickup_by_name'] ?? $record->pickup_by_name;
            } else {
                $record->status = $validated['status'] ?? 'absent';
                $record->checked_in_at = $this->timeOnDate($date, $validated['checked_in_time'] ?? null);
                $record->checked_out_at = $this->timeOnDate($date, $validated['checked_out_time'] ?? null);
                $record->pickup_by_name = $validated['pickup_by_name'] ?? null;
                $record->note = $validated['note'] ?? null;

                if ($record->checked_out_at && ! $record->checked_in_at) {
                    throw ValidationException::withMessages(['checked_out_time' => 'წასვლის დროს სჭირდება მოსვლის დრო.']);
                }
                if ($record->checked_in_at && $record->checked_out_at && $record->checked_out_at->lte($record->checked_in_at)) {
                    throw ValidationException::withMessages(['checked_out_time' => 'წასვლის დრო მოსვლის დროზე გვიანი უნდა იყოს.']);
                }
            }

            $record->save();

            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'action' => 'attendance.updated',
                'subject_type' => AttendanceRecord::class,
                'subject_id' => $record->id,
                'metadata' => json_encode([
                    'child_id' => $child->id,
                    'date' => $date,
                    'status' => $record->status,
                    'action' => $action,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'დასწრების ჩანაწერი განახლდა.');
    }

    private function assertActiveEnrollment(Child $child, int $groupId, string $date): void
    {
        $exists = Enrollment::query()
            ->where('child_id', $child->id)
            ->where('kindergarten_group_id', $groupId)
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date);
            })
            ->exists();

        abort_unless($exists, 404);
    }

    private function actionTimestamp(string $date, string $historicalDefault): CarbonImmutable
    {
        return $date === today()->toDateString()
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($date.' '.$historicalDefault);
    }

    private function timeOnDate(string $date, ?string $time): ?CarbonImmutable
    {
        return $time ? CarbonImmutable::parse($date.' '.$time) : null;
    }
}
