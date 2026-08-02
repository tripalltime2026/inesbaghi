<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\KindergartenGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
                        ->orWhere('username', 'like', "%{$search}%")
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
            'groups' => KindergartenGroup::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'linkableChildren' => Child::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'birth_date', 'birth_year']),
            'enrollmentStatuses' => Enrollment::STATUSES,
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

    public function resetCredentials(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['member', 'parent'], true), 404);

        $username = filled($user->username)
            ? $user->username
            : $this->uniqueUsernameFor($user);
        $temporaryPassword = $this->temporaryPassword();

        $user->forceFill([
            'username' => $username,
            'password' => Hash::make($temporaryPassword),
        ])->save();

        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()->id,
            'action' => 'user.credentials_reset',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'metadata' => json_encode([
                'username' => $username,
                'temporary_password_generated' => true,
            ], JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()
            ->with('success', "{$user->name}-ისთვის ახალი დროებითი პაროლი შეიქმნა.")
            ->with('temporary_credentials', [
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $username,
                'password' => $temporaryPassword,
            ]);
    }

    public function storeChild(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['member', 'parent'], true), 404);

        $validated = $request->validate([
            'child_id' => ['nullable', 'integer', 'exists:children,id'],
            'first_name' => ['nullable', 'required_without:child_id', 'string', 'min:2', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_year' => ['nullable', 'integer', 'min:2018', 'max:'.now()->year],
            'group_id' => ['nullable', 'integer', 'exists:kindergarten_groups,id'],
            'enrollment_status' => ['nullable', 'required_with:group_id', Rule::in(array_keys(Enrollment::STATUSES))],
            'starts_on' => ['nullable', 'required_with:group_id', 'date'],
        ], [
            'first_name.required_without' => 'აირჩიეთ არსებული ბავშვი ან ჩაწერეთ ახალი ბავშვის სახელი.',
            'enrollment_status.required_with' => 'ჯგუფის არჩევისას მიუთითეთ ჩარიცხვის სტატუსი.',
            'starts_on.required_with' => 'ჯგუფის არჩევისას მიუთითეთ დაწყების თარიღი.',
        ]);

        $child = DB::transaction(function () use ($request, $user, $validated): Child {
            $child = filled($validated['child_id'] ?? null)
                ? Child::query()->findOrFail($validated['child_id'])
                : Child::query()->create([
                    'first_name' => trim($validated['first_name']),
                    'last_name' => filled($validated['last_name'] ?? null) ? trim($validated['last_name']) : null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'birth_year' => $validated['birth_year']
                        ?? (filled($validated['birth_date'] ?? null) ? (int) substr($validated['birth_date'], 0, 4) : null),
                ]);

            $isFirstGuardian = ! $child->guardians()->exists();
            $user->children()->syncWithoutDetaching([
                $child->id => [
                    'relationship' => 'მშობელი',
                    'is_primary' => $isFirstGuardian,
                    'can_pick_up' => true,
                ],
            ]);

            if ($user->role === 'member') {
                $user->update(['role' => 'parent']);
            }

            if (filled($validated['group_id'] ?? null)) {
                $currentEnrollment = $child->enrollments()
                    ->whereIn('status', ['pending', 'active', 'paused'])
                    ->latest()
                    ->first();

                $enrollmentData = [
                    'kindergarten_group_id' => $validated['group_id'],
                    'status' => $validated['enrollment_status'],
                    'starts_on' => $validated['starts_on'],
                    'ends_on' => null,
                    'enrolled_at' => $validated['enrollment_status'] === 'active'
                        ? ($currentEnrollment?->enrolled_at ?? now())
                        : null,
                ];

                if ($currentEnrollment) {
                    $currentEnrollment->update($enrollmentData);
                } else {
                    $child->enrollments()->create($enrollmentData);
                }
            }

            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'action' => 'child.linked_to_parent',
                'subject_type' => Child::class,
                'subject_id' => $child->id,
                'metadata' => json_encode([
                    'parent_user_id' => $user->id,
                    'group_id' => $validated['group_id'] ?? null,
                    'enrollment_status' => $validated['enrollment_status'] ?? null,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return $child;
        });

        return back()->with(
            'success',
            "{$child->first_name} {$child->last_name} დაუკავშირდა {$user->name}-ის პროფილს.",
        );
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

    private function uniqueUsernameFor(User $user): string
    {
        $base = Str::of($user->name)
            ->squish()
            ->lower()
            ->toString();

        if ($base === '') {
            $base = 'user-'.$user->id;
        }

        $candidate = $base;
        $attempt = 0;

        while (User::query()
            ->where('username', $candidate)
            ->whereKeyNot($user->id)
            ->exists()) {
            $attempt++;
            $candidate = $base.'-'.$user->id.($attempt > 1 ? '-'.$attempt : '');
        }

        return $candidate;
    }

    private function temporaryPassword(): string
    {
        return Str::upper(Str::random(3))
            .'-'.random_int(1000, 9999)
            .'-'.Str::lower(Str::random(3));
    }
}
