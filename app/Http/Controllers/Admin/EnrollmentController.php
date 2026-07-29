<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Enrollment::STATUSES))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        DB::transaction(function () use ($request, $enrollment, $validated) {
            $lockedEnrollment = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $oldStatus = $lockedEnrollment->status;

            if ($validated['status'] === 'active' && $oldStatus !== 'active') {
                $group = KindergartenGroup::query()->lockForUpdate()->findOrFail($lockedEnrollment->kindergarten_group_id);
                $activeCount = Enrollment::where('kindergarten_group_id', $group->id)
                    ->where('status', 'active')
                    ->where('id', '!=', $lockedEnrollment->id)
                    ->count();

                if ($activeCount >= $group->capacity) {
                    throw ValidationException::withMessages([
                        'status' => 'ჯგუფში თავისუფალი ადგილი აღარ არის. გაზარდეთ capacity ან აირჩიეთ სხვა ჯგუფი.',
                    ]);
                }
            }

            $payload = $validated;
            if ($validated['status'] === 'active' && ! $lockedEnrollment->enrolled_at) {
                $payload['enrolled_at'] = now();
            }
            if (in_array($validated['status'], ['completed', 'cancelled'], true) && empty($payload['ends_on'])) {
                $payload['ends_on'] = today();
            }

            $lockedEnrollment->update($payload);

            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'action' => 'enrollment.updated',
                'subject_type' => Enrollment::class,
                'subject_id' => $lockedEnrollment->id,
                'metadata' => json_encode([
                    'old_status' => $oldStatus,
                    'new_status' => $lockedEnrollment->status,
                    'group_id' => $lockedEnrollment->kindergarten_group_id,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'ჩარიცხვის სტატუსი განახლდა.');
    }
}
