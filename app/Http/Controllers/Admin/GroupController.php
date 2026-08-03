<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KindergartenGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(): View
    {
        KindergartenGroup::ensureDefaults();

        $groups = KindergartenGroup::query()
            ->withCount([
                'enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active'),
                'enrollments as pending_enrollments_count' => fn ($query) => $query->where('status', 'pending'),
            ])
            ->orderBy('age_min_months')
            ->get();

        return view('admin.groups.index', compact('groups'));
    }

    public function show(KindergartenGroup $group): View
    {
        $group->load([
            'enrollments' => fn ($query) => $query
                ->with(['child.guardians'])
                ->orderByRaw("case when status = 'active' then 0 when status = 'pending' then 1 else 2 end")
                ->latest(),
        ]);

        return view('admin.groups.show', compact('group'));
    }

    public function update(Request $request, KindergartenGroup $group): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'capacity' => ['required', 'integer', 'between:1,100'],
            'monthly_fee' => ['required', 'numeric', 'between:0,100000'],
            'academic_year' => ['required', 'regex:/^20\d{2}-20\d{2}$/'],
            'is_active' => ['required', 'boolean'],
        ]);

        $activeCount = $group->enrollments()->where('status', 'active')->count();
        if ((int) $validated['capacity'] < $activeCount) {
            throw ValidationException::withMessages([
                'capacity' => "Capacity ვერ იქნება {$activeCount} აქტიურ ბავშვზე ნაკლები.",
            ]);
        }

        $group->update($validated);

        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()->id,
            'action' => 'group.updated',
            'subject_type' => KindergartenGroup::class,
            'subject_id' => $group->id,
            'metadata' => json_encode([
                'capacity' => $group->capacity,
                'monthly_fee' => $group->monthly_fee,
                'is_active' => $group->is_active,
            ], JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'ჯგუფის პარამეტრები განახლდა.');
    }
}
