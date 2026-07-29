<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    public const STATUSES = [
        'pending' => 'გადასახდელი',
        'partial' => 'ნაწილობრივ გადახდილი',
        'paid' => 'გადახდილი',
        'overdue' => 'ვადაგადაცილებული',
        'waived' => 'ჩამოწერილი',
        'cancelled' => 'გაუქმებული',
    ];

    protected $fillable = [
        'enrollment_id', 'period', 'amount', 'discount_amount', 'paid_amount', 'currency', 'status',
        'due_at', 'paid_at', 'provider_reference', 'notes', 'issued_by_user_id', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->orderByDesc('paid_at');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function totalDue(): float
    {
        return max(0, round((float) $this->amount - (float) $this->discount_amount, 2));
    }

    public function outstandingAmount(): float
    {
        return max(0, round($this->totalDue() - (float) $this->paid_amount, 2));
    }

    public function effectiveStatus(): string
    {
        if (in_array($this->status, ['waived', 'cancelled', 'paid'], true)) {
            return $this->status;
        }

        if ($this->outstandingAmount() <= 0) {
            return 'paid';
        }

        if ($this->due_at?->isPast()) {
            return 'overdue';
        }

        return (float) $this->paid_amount > 0 ? 'partial' : 'pending';
    }
}
