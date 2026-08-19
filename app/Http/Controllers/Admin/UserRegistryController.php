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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'access' => ['nullable', Rule::in(['pending', 'approved', 'debt'])],
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
        if (! filled($filters['segment'] ?? null)) {
            $this->applyLegacyAccessFilter($query, $filters['access'] ?? null);
        }

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

        if ($request->filled('child_first_name') || $request->filled('child_last_name')) {
            $request->merge([
                'child_first_name' => Str::of((string) $request->input('child_first_name'))->squish()->toString(),
                'child_last_name' => Str::of((string) $request->input('child_last_name'))->squish()->toString(),
            ]);
        }

        $validated = $request->validate([
            'child_id' => [
                'nullable',
                'integer',
                Rule::exists('child_guardians', 'child_id')
                    ->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'child_first_name' => ['nullable', 'string', 'min:2', 'max:100'],
            'child_last_name' => ['nullable', 'string', 'min:2', 'max:100'],
            'child_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('kindergarten_groups', 'id')->where('is_active', true),
            ],
            'enroll_now' => ['nullable', 'boolean'],
            'starts_on' => ['nullable', 'date'],
        ], [
            'child_id.exists' => 'არჩეული ბავშვი ამ მშობლის ანგარიშს არ ეკუთვნის.',
            'child_first_name.min' => 'ბავშვის სახელი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_last_name.min' => 'ბავშვის გვარი მინიმუმ 2 სიმბოლოს უნდა შეიცავდეს.',
            'child_birth_date.before_or_equal' => 'ბავშვის დაბადების თარიღი მომავალში ვერ იქნება.',
            'group_id.exists' => 'არჩეული ჯგუფი აქტიური აღარ არის.',
        ]);

        $childId = $validated['child_id'] ?? null;
        if (! $childId) {
            $missingChildFields = collect(['child_first_name', 'child_last_name', 'child_birth_date'])
                ->filter(fn (string $field) => blank($validated[$field] ?? null));

            if ($missingChildFields->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'child_first_name' => 'ბავშვის მიბმისთვის შეავსეთ სახელი, გვარი და დაბადების თარიღი.',
                ]);
            }
        }

        $enrollNow = $request->has('enroll_now')
            ? $request->boolean('enroll_now')
            : filled($validated['group_id'] ?? null);

        if ($enrollNow && blank($validated['group_id'] ?? null)) {
            throw ValidationException::withMessages([
                'group_id' => 'ჩარიცხვისთვის აირჩიეთ ჯგუფი.',
            ]);
        }

        $startsOn = $validated['starts_on'] ?? now()->toDateString();

        [$child, $enrolled] = DB::transaction(function () use ($request, $user, $validated, $childId, $enrollNow, $startsOn): array {
            if ($childId) {
                $child = $user->children()->whereKey($childId)->firstOrFail();
            } else {
                $isPrimary = ! $user->children()->exists();
                $child = Child::query()->create([
                    'first_name' => $validated['child_first_name'],
                    'last_name' => $validated['child_last_name'],
                    'birth_date' => $validated['child_birth_date'],
                    'birth_year' => (int) substr($validated['child_birth_date'], 0, 4),
                ]);

                $user->children()->attach($child->id, [
                    'relationship' => 'მშობელი',
                    'is_primary' => $isPrimary,
                    'can_pick_up' => true,
                ]);

                DB::table('audit_logs')->insert([
                    'actor_user_id' => $request->user()->id,
                    'action' => 'parent_child.linked_by_admin',
                    'subject_type' => Child::class,
                    'subject_id' => $child->id,
                    'metadata' => json_encode([
                        'parent_user_id' => $user->id,
                        'source' => 'admin_user_registry',
                    ], JSON_THROW_ON_ERROR),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                ]);
            }

            if (! $enrollNow) {
                if ($user->role === 'member') {
                    $user->update(['role' => 'parent']);
                }

                return [$child, false];
            }

            $group = KindergartenGroup::query()
                ->whereKey($validated['group_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $activeCount = Enrollment::query()
                ->where('kindergarten_group_id', $group->id)
                ->where('status', 'active')
                ->where('child_id', '!=', $child->id)
                ->lockForUpdate()
                ->count();

            if ($activeCount >= $group->capacity) {
                throw ValidationException::withMessages([
                    'group_id' => "{$group->name} შევსებულია. აირჩიეთ სხვა ჯგუფი.",
                ]);
            }

            $openEnrollments = $child->enrollments()
                ->whereIn('status', ['pending', 'active', 'paused'])
                ->lockForUpdate()
                ->get();

            $sameGroupEnrollment = $openEnrollments
                ->first(fn (Enrollment $enrollment) => (int) $enrollment->kindergarten_group_id === (int) $group->id);

            $previousDay = Carbon::parse($startsOn)->subDay()->toDateString();

            foreach ($openEnrollments as $openEnrollment) {
                if ($sameGroupEnrollment && $openEnrollment->is($sameGroupEnrollment)) {
                    continue;
                }

                $openEnrollment->update([
                    'status' => 'completed',
                    'ends_on' => $previousDay,
                ]);
            }

            if ($sameGroupEnrollment) {
                $sameGroupEnrollment->update([
                    'status' => 'active',
                    'starts_on' => $startsOn,
                    'ends_on' => null,
                    'enrolled_at' => $sameGroupEnrollment->enrolled_at ?? now(),
                ]);
            } else {
                $child->enrollments()->create([
                    'kindergarten_group_id' => $group->id,
                    'status' => 'active',
                    'starts_on' => $startsOn,
                    'ends_on' => null,
                    'enrolled_at' => now(),
                ]);
            }

            $user->update([
                'role' => 'parent',
                'status' => 'active',
                'club_access_approved_at' => $user->club_access_approved_at ?? now(),
                'club_access_approved_by_user_id' => $request->user()->id,
            ]);

            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'action' => 'parent_child.verified_and_enrolled',
                'subject_type' => Child::class,
                'subject_id' => $child->id,
                'metadata' => json_encode([
                    'parent_user_id' => $user->id,
                    'group_id' => $group->id,
                    'starts_on' => $startsOn,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return [$child, true];
        });

        if ($enrolled) {
            return back()->with(
                'success',
                "{$child->first_name} {$child->last_name} ჯგუფში ჩაირიცხა. მშობლის დასტური და Parent Club ავტომატურად გააქტიურდა.",
            );
        }

        return back()->with(
            'success',
            "{$child->first_name} {$child->last_name} მშობლის ანგარიშთან დაკავშირებულია. ჯგუფში ჩარიცხვა შეგიძლიათ იმავე ბარათიდან.",
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

    private function applyLegacyAccessFilter(Builder $query, ?string $filter): void
    {
        match ($filter) {
            'pending' => $query->whereNull('club_access_approved_at'),
            'approved' => $query->whereNotNull('club_access_approved_at'),
            'debt' => $query->whereColumn('payment_due', '>', 'payment_paid'),
            default => null,
        };
    }

    private function applyClubEligible(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNotNull('club_access_approved_at')
            ->whereHas('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                ->where('status', 'active')
                ->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('is_active', true)));
    }

    private function applyApprovedIncomplete(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNotNull('club_access_approved_at')
            ->whereDoesntHave('children.enrollments', fn (Builder $enrollmentQuery) => $enrollmentQuery
                ->where('status', 'active')
                ->whereHas('group', fn (Builder $groupQuery) => $groupQuery->where('is_active', true)));
    }

    private function applyWithoutClubAccess(Builder $query): Builder
    {
        return $query->where(function (Builder $blocked): void {
            $blocked->where('status', '!=', 'active')
                ->orWhereNull('club_access_approved_at')
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
