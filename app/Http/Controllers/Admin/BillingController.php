<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KindergartenGroup;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'status' => ['nullable', Rule::in(array_keys(Payment::STATUSES))],
            'group_id' => ['nullable', 'integer', 'exists:kindergarten_groups,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        Payment::query()
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_at', '<', now())
            ->whereRaw('(amount - discount_amount) > paid_amount')
            ->update(['status' => 'overdue']);

        $period = $filters['period'] ?? now()->format('Y-m');
        $query = Payment::query()
            ->with([
                'enrollment.child.guardians',
                'enrollment.group',
            ])
            ->where('period', $period)
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['group_id'] ?? null, fn ($builder, $groupId) => $builder->whereHas(
                'enrollment', fn ($enrollmentQuery) => $enrollmentQuery->where('kindergarten_group_id', $groupId),
            ))
            ->when($filters['search'] ?? null, function ($builder, $search) {
                $builder->whereHas('enrollment.child', function ($childQuery) use ($search) {
                    $childQuery->whereRaw("TRIM(COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')) LIKE ?", ['%'.$search.'%'])
                        ->orWhereHas('guardians', fn ($guardianQuery) => $guardianQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%'));
                });
            });

        $summaryRows = (clone $query)->get();
        $summary = [
            'charged' => $summaryRows->reject(fn ($payment) => in_array($payment->status, ['cancelled', 'waived'], true))->sum(fn ($payment) => $payment->totalDue()),
            'paid' => $summaryRows->sum(fn ($payment) => (float) $payment->paid_amount),
            'outstanding' => $summaryRows->reject(fn ($payment) => in_array($payment->status, ['cancelled', 'waived'], true))->sum(fn ($payment) => $payment->outstandingAmount()),
            'overdue_count' => $summaryRows->where('status', 'overdue')->count(),
        ];

        $payments = $query->latest('due_at')->paginate(25)->withQueryString();
        $groups = KindergartenGroup::query()->orderBy('name')->get();

        return view('admin.payments.index', compact('payments', 'groups', 'filters', 'period', 'summary'));
    }

    public function generate(Request $request, BillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'due_at' => ['required', 'date'],
            'group_id' => ['nullable', 'integer', 'exists:kindergarten_groups,id'],
        ]);

        $result = $billing->generate(
            $validated['period'],
            $validated['due_at'],
            $validated['group_id'] ?? null,
            $request->user()->id,
            $request->ip(),
        );

        return back()->with('success', "შეიქმნა {$result['created']} დარიცხვა; {$result['skipped']} უკვე არსებობდა.");
    }

    public function show(Payment $payment): View
    {
        $payment->load([
            'enrollment.child.guardians',
            'enrollment.group',
            'transactions.recordedBy',
            'issuedBy',
        ]);

        return view('admin.payments.show', compact('payment'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'waived', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($request, $payment, $validated) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $maximumDiscount = max(0, (float) $locked->amount - (float) $locked->paid_amount);

            if ((float) $validated['discount_amount'] > $maximumDiscount) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'ფასდაკლება ვერ გადააჭარბებს დარჩენილ გადასახდელ თანხას.',
                ]);
            }

            $locked->discount_amount = $validated['discount_amount'];
            $locked->notes = $validated['notes'] ?? null;
            $locked->status = $validated['status'];
            $locked->cancelled_at = $validated['status'] === 'cancelled' ? now() : null;

            if ($validated['status'] === 'pending' && (float) $locked->paid_amount > 0) {
                $locked->status = $locked->outstandingAmount() <= 0 ? 'paid' : 'partial';
            }

            $locked->save();
            $this->audit($request, 'payment.updated', $locked, [
                'status' => $locked->status,
                'discount_amount' => $locked->discount_amount,
            ]);
        });

        return back()->with('success', 'დარიცხვა განახლდა.');
    }

    public function storeTransaction(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(array_keys(PaymentTransaction::METHODS))],
            'reference' => ['nullable', 'string', 'max:190'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $payment, $validated) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (in_array($locked->status, ['waived', 'cancelled'], true)) {
                throw ValidationException::withMessages(['amount' => 'ჩამოწერილ ან გაუქმებულ დარიცხვაზე გადახდა ვერ დაემატება.']);
            }

            if ((float) $validated['amount'] > $locked->outstandingAmount()) {
                throw ValidationException::withMessages(['amount' => 'თანხა აღემატება დარჩენილ დავალიანებას.']);
            }

            $transaction = $locked->transactions()->create([
                ...$validated,
                'recorded_by_user_id' => $request->user()->id,
            ]);

            $paidAmount = (float) $locked->transactions()->sum('amount');
            $locked->paid_amount = $paidAmount;
            $locked->status = $locked->outstandingAmount() <= 0 ? 'paid' : 'partial';
            $locked->paid_at = $locked->status === 'paid' ? $transaction->paid_at : null;
            $locked->save();

            $this->audit($request, 'payment.transaction_recorded', $locked, [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'method' => $transaction->method,
                'status' => $locked->status,
            ]);
        });

        return back()->with('success', 'გადახდა დაფიქსირდა.');
    }

    private function audit(Request $request, string $action, Payment $payment, array $metadata): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
