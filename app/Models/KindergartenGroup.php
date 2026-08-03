<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KindergartenGroup extends Model
{
    public const DEFAULTS = [
        [
            'name' => '2-3 წელი',
            'slug' => '2-3',
            'age_min_months' => 24,
            'age_max_months' => 35,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ],
        [
            'name' => '3-4 წელი',
            'slug' => '3-4',
            'age_min_months' => 36,
            'age_max_months' => 47,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ],
        [
            'name' => '4-5 წელი',
            'slug' => '4-5',
            'age_min_months' => 48,
            'age_max_months' => 59,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ],
        [
            'name' => '5-6 წელი',
            'slug' => '5-6',
            'age_min_months' => 60,
            'age_max_months' => 71,
            'capacity' => 20,
            'monthly_fee' => 600,
            'academic_year' => '2026-2027',
            'is_active' => true,
        ],
    ];

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

    public static function ensureDefaults(): void
    {
        if (static::query()->exists()) {
            return;
        }

        foreach (self::DEFAULTS as $attributes) {
            static::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                $attributes,
            );
        }
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
