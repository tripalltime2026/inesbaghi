<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserRegistryController extends Controller
{
    public const FILTERS = [
        'registered' => 'მხოლოდ რეგისტრირებული',
        'applicant' => 'განაცხადის მქონე',
        'linked' => 'ბავშვთან დაკავშირებული',
        'pending' => 'ჩარიცხვა დასამტკიცებელია',
        'club' => 'კლუბზე დაშვებული',
    ];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'membership' => ['nullable', Rule::in(array_keys(self::FILTERS))],
        ]);

        $query = User::query()
            ->with(['children.enrollments.group'])
            ->withCount('children')
            ->addSelect([
                'application_count' => AdmissionApplication::query()
                    ->selectRaw('count(*)')
                    ->where(function ($applicationQuery): void {
                        $applicationQuery->whereColumn('admission_applications.guardian_user_id', 'users.id')
                            ->orWhereColumn('admission_applications.phone', 'users.phone');
                    }),
            ])
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $this->applyMembershipFilter($query, $filters['membership'] ?? null);

        return view('admin.users.index', [
            'users' => $query->latest()->paginate(30)->withQueryString(),
            'filters' => $filters,
            'membershipFilters' => self::FILTERS,
            'counts' => [
                'total' => User::count(),
                'registered' => User::whereDoesntHave('children')->count(),
                'linked' => User::whereHas('children')->count(),
                'club' => $this->clubEligibleQuery()->count(),
            ],
        ]);
    }

    private function applyMembershipFilter(Builder $query, ?string $filter): void
    {
        match ($filter) {
            'registered' => $query->whereDoesntHave('children'),
            'applicant' => $query->whereExists(function ($applicationQuery): void {
                $applicationQuery->selectRaw('1')
                    ->from('admission_applications')
                    ->where(function ($match): void {
                        $match->whereColumn('admission_applications.guardian_user_id', 'users.id')
                            ->orWhereColumn('admission_applications.phone', 'users.phone');
                    });
            }),
            'linked' => $query->whereHas('children'),
            'pending' => $query->whereHas('children.enrollments', fn (Builder $enrollments) => $enrollments->where('status', 'pending')),
            'club' => $query
                ->where('status', 'active')
                ->whereNotNull('phone_verified_at')
                ->whereHas('children.enrollments', fn (Builder $enrollments) => $enrollments
                    ->where('status', 'active')
                    ->whereHas('group', fn (Builder $groups) => $groups->where('is_active', true))),
            default => null,
        };
    }

    private function clubEligibleQuery(): Builder
    {
        return User::query()
            ->where('status', 'active')
            ->whereNotNull('phone_verified_at')
            ->whereHas('children.enrollments', fn (Builder $enrollments) => $enrollments
                ->where('status', 'active')
                ->whereHas('group', fn (Builder $groups) => $groups->where('is_active', true)));
    }
}
