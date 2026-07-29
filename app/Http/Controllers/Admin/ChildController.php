<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'group_id' => ['nullable', 'integer', 'exists:kindergarten_groups,id'],
            'status' => ['nullable', Rule::in(array_keys(Enrollment::STATUSES))],
        ]);

        $children = Child::query()
            ->with(['guardians', 'enrollments.group'])
            ->when($filters['search'] ?? null, function (Builder $query, string $term) {
                $query->where(function (Builder $builder) use ($term) {
                    $builder->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhereHas('guardians', fn (Builder $guardianQuery) => $guardianQuery
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%"));
                });
            })
            ->when($filters['group_id'] ?? null, fn (Builder $query, int $groupId) => $query
                ->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                    ->where('kindergarten_group_id', $groupId)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query
                ->whereHas('enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                    ->where('status', $status)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.children.index', [
            'children' => $children,
            'groups' => KindergartenGroup::orderBy('name')->get(),
            'statuses' => Enrollment::STATUSES,
            'filters' => $filters,
        ]);
    }

    public function show(Child $child): View
    {
        $child->load([
            'guardians',
            'enrollments' => fn ($query) => $query->with(['group', 'payments'])->latest(),
        ]);

        return view('admin.children.show', [
            'child' => $child,
            'statuses' => Enrollment::STATUSES,
        ]);
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'birth_year' => ['nullable', 'integer', 'between:2018,2026'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'medical_notes' => ['nullable', 'string', 'max:5000'],
            'photo_consent' => ['required', 'boolean'],
        ]);

        $photoConsent = (bool) $validated['photo_consent'];
        unset($validated['photo_consent']);
        $validated['photo_consent_at'] = $photoConsent ? ($child->photo_consent_at ?? now()) : null;

        $child->update($validated);

        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()->id,
            'action' => 'child.updated',
            'subject_type' => Child::class,
            'subject_id' => $child->id,
            'metadata' => json_encode(['photo_consent' => $photoConsent], JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'ბავშვის პროფილი განახლდა.');
    }
}
