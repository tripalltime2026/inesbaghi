<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserRegistryController extends Controller
{
    public const FILTERS = [
        'pending' => 'დადასტურების მოლოდინში',
        'approved' => 'დადასტურებული',
        'debt' => 'დავალიანების მქონე',
    ];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'access' => ['nullable', Rule::in(array_keys(self::FILTERS))],
        ]);

        $query = $this->parentQuery()
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

        $this->applyFilter($query, $filters['access'] ?? null);

        $billingUsers = $this->parentQuery()->get(['payment_due', 'payment_paid']);

        return view('admin.users.index', [
            'users' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'accessFilters' => self::FILTERS,
            'counts' => [
                'total' => $this->parentQuery()->count(),
                'pending' => $this->parentQuery()->whereNull('club_access_approved_at')->count(),
                'approved' => $this->parentQuery()->whereNotNull('club_access_approved_at')->count(),
                'outstanding' => (float) $billingUsers->sum(fn (User $user) => $user->paymentOutstanding()),
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['member', 'parent'], true), 404);

        $validated = $request->validate([
            'access_approved' => ['required', 'boolean'],
            'payment_due' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'payment_paid' => ['required', 'numeric', 'min:0', 'max:999999.99', 'lte:payment_due'],
            'payment_due_at' => ['nullable', 'date'],
            'payment_note' => ['nullable', 'string', 'max:1500'],
        ], [
            'payment_paid.lte' => 'გადახდილი თანხა ვერ იქნება გადასახდელ თანხაზე მეტი.',
        ]);

        $approved = (bool) $validated['access_approved'];

        $user->update([
            'club_access_approved_at' => $approved
                ? ($user->club_access_approved_at ?? now())
                : null,
            'club_access_approved_by_user_id' => $approved ? $request->user()->id : null,
            'payment_due' => $validated['payment_due'],
            'payment_paid' => $validated['payment_paid'],
            'payment_due_at' => $validated['payment_due_at'] ?? null,
            'payment_note' => $validated['payment_note'] ?? null,
        ]);

        return back()->with('success', "{$user->name}-ის წვდომა და გადასახდელი ინფორმაცია შენახულია.");
    }

    private function parentQuery(): Builder
    {
        return User::query()->whereIn('role', ['member', 'parent']);
    }

    private function applyFilter(Builder $query, ?string $filter): void
    {
        match ($filter) {
            'pending' => $query->whereNull('club_access_approved_at'),
            'approved' => $query->whereNotNull('club_access_approved_at'),
            'debt' => $query->whereColumn('payment_due', '>', 'payment_paid'),
            default => null,
        };
    }
}
