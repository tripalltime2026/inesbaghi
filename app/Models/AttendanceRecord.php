<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUSES = [
        'present' => 'დასწრებული',
        'absent' => 'არ გამოცხადდა',
        'excused' => 'გაცდენა შეთანხმებულია',
        'sick' => 'ავადმყოფობა',
    ];

    protected $fillable = [
        'child_id', 'kindergarten_group_id', 'attendance_date', 'status',
        'checked_in_at', 'checked_out_at', 'recorded_by_user_id', 'pickup_by_name', 'note',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
