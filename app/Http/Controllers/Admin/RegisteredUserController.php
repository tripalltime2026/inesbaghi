<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'access' => ['nullable', Rule::in(['verified', 'without_access', 'pending_enrollment'])],
        ]);

        $query = $this->accountQuery()
            ->with(['children.enrollments' => fn ($enrollments) => $enrollments->with('group')->latest()])
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.preg_replace('/\D+/', '', $search).'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when(($filters['access'] ?? null) === 'verified', fn (Builder $builder) => $this->verifiedParentScope($builder))
            ->when(($filters['access'] ?? null) === 'without_access', fn (Builder $builder) => $builder
                ->whereDoesntHave('children.enrollments', fn (Builder $enrollments) => $enrollments
                    ->where('status', 'active')
                    ->whereHas('group', fn (Builder $groups) => $groups->where('is_active', true))))
            ->when(($filters['access'] ?? null) === 'pending_enrollment', fn (Builder $builder) => $builder
                ->whereHas('children.enrollments', fn (Builder $enrollments) => $enrollments->where('status', 'pending')));

        $users = $query->latest()->paginate(25)->withQueryString();
        $phones = $users->getCollection()->pluck('phone')->filter()->values();
        $applications = AdmissionApplication::query()
            ->whereIn('phone', $phones)
            ->latest()
            ->get()
            ->unique('phone')
            ->keyBy('phone');

        $totalAccounts = $this->accountQuery()->count();
        $verifiedParents = $this->verifiedParentScope($this->accountQuery())->count();
        $pendingEnrollments = $this->accountQuery()
            ->whereHas('children.enrollments', fn (Builder $enrollments) => $enrollments->where('status', 'pending'))
            ->count();

        return view('admin.users.index', [
            'users' => $users,
            'applications' => $applications,
            'applicationStatuses' => AdmissionApplication::STATUSES,
            'filters' => $filters,
            'stats' => [
                'total' => $totalAccounts,
                'verified' => $verifiedParents,
                'without_access' => max(0, $totalAccounts - $verifiedParents),
                'pending_enrollment' => $pendingEnrollments,
            ],
        ]);
    }

    private function accountQuery(): Builder
    {
        return User::query()->whereIn('role', ['member', 'parent']);
    }

    private function verifiedParentScope(Builder $builder): Builder
    {
        return $builder
            ->where('role', 'parent')
            ->where('status', 'active')
            ->whereNotNull('phone_verified_at')
            ->whereHas('children.enrollments', fn (Builder $enrollments) => $enrollments
                ->where('status', 'active')
                ->whereHas('group', fn (Builder $groups) => $groups->where('is_active', true)));
    }
}
