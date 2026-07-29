<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    public const STATUSES = [
        'pending' => 'დასამტკიცებელი',
        'active' => 'აქტიური',
        'paused' => 'შეჩერებული',
        'completed' => 'დასრულებული',
        'cancelled' => 'გაუქმებული',
    ];

    protected $fillable = [
        'child_id', 'kindergarten_group_id', 'status', 'starts_on', 'ends_on', 'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'enrolled_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KindergartenGroup::class, 'kindergarten_group_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
