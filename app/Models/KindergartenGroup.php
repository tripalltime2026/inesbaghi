<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KindergartenGroup extends Model
{
    protected $fillable = [
        'name', 'slug', 'age_min_months', 'age_max_months', 'capacity',
        'monthly_fee', 'academic_year', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
