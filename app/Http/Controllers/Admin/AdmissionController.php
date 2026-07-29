<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(AdmissionApplication::STATUSES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'tour' => ['nullable', Rule::in(['today', 'upcoming', 'requested'])],
        ]);

        $query = AdmissionApplication::query()
            ->with('assignedTo')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['assigned_to'] ?? null, fn ($builder, $userId) => $builder->where('assigned_to_user_id', $userId))
            ->when(($filters['tour'] ?? null) === 'today', fn ($builder) => $builder->whereDate('tour_scheduled_at', today()))
            ->when(($filters['tour'] ?? null) === 'upcoming', fn ($builder) => $builder->where('tour_scheduled_at', '>=', now()))
            ->when(($filters['tour'] ?? null) === 'requested', fn ($builder) => $builder->where('wants_tour', true));

        return view('admin.admissions.index', [
            'applications' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => AdmissionApplication::STATUSES,
            'statusCounts' => AdmissionApplication::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'assignableUsers' => User::where('status', 'active')
                ->whereIn('role', ['admin', 'teacher'])
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
            'filters' => $filters,
        ]);
    }

    public function show(AdmissionApplication $application): View
    {
        $application->load(['assignedTo', 'guardian', 'convertedChild', 'notes.author']);

        return view('admin.admissions.show', [
            'application' => $application,
            'statuses' => AdmissionApplication::STATUSES,
            'assignableUsers' => User::where('status', 'active')
                ->whereIn('role', ['admin', 'teacher'])
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
        ]);
    }

    public function update(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(AdmissionApplication::STATUSES))],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'follow_up_at' => ['nullable', 'date'],
            'tour_scheduled_at' => ['nullable', 'date'],
        ]);

        $oldStatus = $application->status;
        if ($oldStatus !== $validated['status']) {
            $validated['status_updated_at'] = now();
        }

        $application->update($validated);
        $this->audit($request, 'admission.updated', $application, [
            'old_status' => $oldStatus,
            'new_status' => $application->status,
            'assigned_to_user_id' => $application->assigned_to_user_id,
        ]);

        return back()->with('success', 'განაცხადი განახლდა.');
    }

    public function storeNote(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        $note = $application->notes()->create([
            'author_user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_internal' => true,
        ]);

        $this->audit($request, 'admission.note_created', $application, ['note_id' => $note->id]);

        return back()->with('success', 'შიდა კომენტარი დაემატა.');
    }

    public function convert(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $converted = DB::transaction(function () use ($request, $application) {
            $locked = AdmissionApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($locked->converted_at) {
                return false;
            }

            $guardian = User::firstOrCreate(
                ['phone' => $locked->phone],
                [
                    'name' => $locked->parent_name,
                    'role' => 'parent',
                    'status' => 'active',
                ]
            );

            if ($guardian->role === 'member') {
                $guardian->update([
                    'name' => $locked->parent_name,
                    'role' => 'parent',
                    'status' => 'active',
                ]);
            }

            [$firstName, $lastName] = $this->splitName($locked->child_name);
            $child = Child::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birth_year' => $locked->birth_year,
            ]);

            $child->guardians()->attach($guardian->id, [
                'relationship' => 'parent',
                'is_primary' => true,
                'can_pick_up' => true,
            ]);

            $group = KindergartenGroup::where('slug', $locked->preferred_group)
                ->where('is_active', true)
                ->first();

            $enrollment = null;
            if ($group) {
                $enrollment = Enrollment::create([
                    'child_id' => $child->id,
                    'kindergarten_group_id' => $group->id,
                    'status' => 'pending',
                    'starts_on' => sprintf('%d-09-01', (int) $locked->academic_year),
                ]);
            }

            $locked->update([
                'guardian_user_id' => $guardian->id,
                'converted_child_id' => $child->id,
                'converted_at' => now(),
                'status' => $enrollment ? 'enrolled' : 'approved',
                'status_updated_at' => now(),
            ]);

            $this->audit($request, 'admission.converted', $locked, [
                'guardian_user_id' => $guardian->id,
                'child_id' => $child->id,
                'enrollment_id' => $enrollment?->id,
            ]);

            return true;
        });

        if (! $converted) {
            return back()->with('info', 'ეს განაცხადი უკვე გარდაქმნილია.');
        }

        return back()->with('success', 'მშობელი, ბავშვი და ჩარიცხვის ჩანაწერი შეიქმნა.');
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0], $parts[1] ?? null];
    }

    private function audit(Request $request, string $action, AdmissionApplication $application, array $metadata = []): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => AdmissionApplication::class,
            'subject_id' => $application->id,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
