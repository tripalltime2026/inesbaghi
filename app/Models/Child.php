<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Child extends Model
{
    protected $fillable = ['first_name', 'last_name', 'birth_date', 'birth_year', 'medical_notes', 'photo_consent_at'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'photo_consent_at' => 'datetime',
        ];
    }

    public function setAttribute($key, $value)
    {
        // Some existing production databases predate the optional birth_year
        // column. Child creation must still work there because birth_date is the
        // source of truth and birth_year is only a derived convenience field.
        if ($key === 'birth_year' && ! Schema::hasColumn($this->getTable(), 'birth_year')) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'child_guardians')
            ->withPivot(['relationship', 'is_primary', 'can_pick_up'])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class)->latest('attendance_date');
    }
}
