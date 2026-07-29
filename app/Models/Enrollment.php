<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
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
}
