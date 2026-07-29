<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    public const METHODS = [
        'cash' => 'ნაღდი',
        'bank_transfer' => 'საბანკო გადარიცხვა',
        'card' => 'ბარათი',
        'online' => 'ონლაინ გადახდა',
    ];

    protected $fillable = [
        'payment_id', 'recorded_by_user_id', 'amount', 'method', 'reference', 'paid_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
