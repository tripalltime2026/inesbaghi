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
        'registered' => 'ყველა მოქმედი რეგისტრაცია',
        'awaiting' => 'დადასტურების მოლოდინში',
        'club_active' => 'კლუბის აქტიური წევრები',
        'approved_incomplete' => 'დამტკიცებული, მაგრამ არასრული',
        'no_access' => 'კლუბზე წვდომის გარეშე',
        'no_child' => 'ბავშვის გარეშე',
        'no_enrollment' => 'აქტიური ჯგუფის გარეშე',
        'suspended' => 'დროებით შეჩერებული',
        'cancelled' => 'გაუქმებული',
        'debt' => 'დავალიანების მქონე',
    ];

    public const ACCOUNT_STATUSES = [
        'pending' => 'რეგისტრირებულია — განხილვაში',
        'active' => 'აქტიური ანგარიში',
        'suspended' => 'დროებით შეჩერებული',
        'cancelled' => 'გაუქმებული',
    ];

    public function __invoke(Request $request): View
    {
        KindergartenGroup::ensureDefaults();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'segment' => ['nullable', Rule::in(array_keys(self::FILTERS))],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('kindergarten_groups', 'id')->where('is_active', true),
            ],
        ]);

        $query = $this->parentQuery()
            ->with([
                'children.enrollments' => fn ($enrollmentQuery) => $enrollmentQuery
                    ->with('group')
                    ->latest(),
            ])
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
                $search = trim($search);
                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('children', fn (Builder $childQuery) => $childQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['group_id'] ?? null, fn (Builder $builder, int $groupId) => $builder
                ->whereHas('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                    ->where('kindergarten_group_id', $groupId)));

        $this->applyFilter($query, $filters['segment'] ?? null);

        $billingUsers = $this->parentQuery()->get(['payment_due', 'payment_paid']);
        $counts = $this->segmentCounts();
        $counts['outstanding'] = (float) $billingUsers->sum(fn (User $user) => $user->paymentOutstanding());

        return view('admin.users.index', [
            'users' => $query
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 WHEN 'suspended' THEN 2 ELSE 3 END")
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'segments' => self::FILTERS,
            'accountStatuses' => self::ACCOUNT_STATUSES,
            'groups' => KindergartenGroup::query()
                ->where('is_active', true)
                ->orderBy('age_min_months')
                ->orderBy('name')
                ->get(),
            'linkableChildren' => Child::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'birth_date', 'birth_year']),
            'enrollmentStatuses' => Enrollment::STATUSES,
            'counts' => $counts,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['member', 'parent'], true), 404);

        $validated = $request->validate([
            'account_status' => ['nullable', Rule::in(array_keys(self::ACCOUNT_STATUSES))],
            'access_approved' => ['required', 'boolean'],
            'payment_due' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'payment_paid' => ['required', 'numeric', 'min:0', 'max:999999.99', 'lte:payment_due'],
            'payment_due_at' => ['nullable', 'date'],
            'payment_note' => ['nullable', 'string', 'max:1500'],
        ], [
            'payment_paid.lte' => 'გადახდილი თანხა ვერ იქნება გადასახდელ თანხაზე მეტი.',
        ]);

        $approved = (bool) $validated['access_approved'];
        $oldStatus = $user->status;
        $newStatus = $validated['account_status'] ?? $user->status;

        DB::transaction(function () use ($request, $user, $validated, $approved, $oldStatus, $newStatus): void {
            $user->update([
                'status' => $newStatus,
                'club_access_approved_at' => $approved
                    ? ($user->club_access_approved_at ?? now())
                    : null,
                'club_access_approved_by_user_id' => $approved ? $request->user()->id : null,
                'payment_due' => $validated['payment_due'],
                'payment_paid' => $validated['payment_paid'],
                'payment_due_at' => $validated['payment_due_at'] ?? null,
                'payment_note' => $validated['payment_note'] ?? null,
            ]);

            if ($newStatus !== 'active') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'action' => 'user.registry_status_updated',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'metadata' => json_encode([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'club_access_approved' => $approved,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        $statusLabel = self::ACCOUNT_STATUSES[$newStatus] ?? $newStatus;

        return back()->with('success', "{$user->name}-ის სტატუსი განახლდა: {$statusLabel}.");
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
                    'birth_year' => filled($validated['birth_date'] ?? null)
                        ? (int) substr($validated['birth_date'], 0, 4)
                        : null,
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
            'registered' => $query->whereIn('status', ['pending', 'active']),
            'awaiting' => $query
                ->whereIn('status', ['pending', 'active'])
                ->whereNull('club_access_approved_at'),
            'club_active' => $this->applyClubEligible($query),
            'approved_incomplete' => $this->applyApprovedIncomplete($query),
            'no_access' => $this->applyWithoutClubAccess($query),
            'no_child' => $query->whereDoesntHave('children'),
            'no_enrollment' => $query
                ->whereHas('children')
                ->whereDoesntHave('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                    ->where('status', 'active')
                    ->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('is_active', true))),
            'suspended' => $query->where('status', 'suspended'),
            'cancelled' => $query->where('status', 'cancelled'),
            'debt' => $query->whereColumn('payment_due', '>', 'payment_paid'),
            default => null,
        };
    }

    private function applyClubEligible(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNotNull('club_access_approved_at')
            ->where(function (Builder $identity): void {
                $identity->where(function (Builder $credentials): void {
                    $credentials->whereNotNull('username')->whereNotNull('password');
                })
                    ->orWhereNotNull('phone_verified_at')
                    ->orWhereNotNull('email_verified_at');
            })
            ->whereHas('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                ->where('status', 'active')
                ->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('is_active', true)));
    }

    private function applyApprovedIncomplete(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNotNull('club_access_approved_at')
            ->where(function (Builder $blocked): void {
                $blocked->where(function (Builder $identityMissing): void {
                    $identityMissing
                        ->where(function (Builder $credentials): void {
                            $credentials->whereNull('username')->orWhereNull('password');
                        })
                        ->whereNull('phone_verified_at')
                        ->whereNull('email_verified_at');
                })
                    ->orWhereDoesntHave('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                        ->where('status', 'active')
                        ->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('is_active', true)));
            });
    }

    private function applyWithoutClubAccess(Builder $query): Builder
    {
        return $query->where(function (Builder $blocked): void {
            $blocked->where('status', '!=', 'active')
                ->orWhereNull('club_access_approved_at')
                ->orWhere(function (Builder $identityMissing): void {
                    $identityMissing
                        ->where(function (Builder $credentials): void {
                            $credentials->whereNull('username')->orWhereNull('password');
                        })
                        ->whereNull('phone_verified_at')
                        ->whereNull('email_verified_at');
                })
                ->orWhereDoesntHave('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                    ->where('status', 'active')
                    ->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('is_active', true)));
        });
    }

    private function segmentCounts(): array
    {
        $clubActive = $this->applyClubEligible($this->parentQuery())->count();
        $approvedIncomplete = $this->applyApprovedIncomplete($this->parentQuery())->count();
        $withoutAccess = $this->applyWithoutClubAccess($this->parentQuery())->count();

        return [
            'total' => $this->parentQuery()->count(),
            'registered' => $this->parentQuery()->whereIn('status', ['pending', 'active'])->count(),
            'awaiting' => $this->parentQuery()
                ->whereIn('status', ['pending', 'active'])
                ->whereNull('club_access_approved_at')
                ->count(),
            'club_active' => $clubActive,
            'approved_incomplete' => $approvedIncomplete,
            'no_access' => $withoutAccess,
            'no_child' => $this->parentQuery()->whereDoesntHave('children')->count(),
            'no_enrollment' => $this->parentQuery()
                ->whereHas('children')
                ->whereDoesntHave('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                    ->where('status', 'active')
                    ->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('is_active', true)))
                ->count(),
            'suspended' => $this->parentQuery()->where('status', 'suspended')->count(),
            'cancelled' => $this->parentQuery()->where('status', 'cancelled')->count(),
            'debt' => $this->parentQuery()->whereColumn('payment_due', '>', 'payment_paid')->count(),
        ];
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
