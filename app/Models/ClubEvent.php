<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubEvent extends Model
{
    public const STATUSES = [
        'draft' => 'მონახაზი',
        'published' => 'გამოქვეყნებული',
        'cancelled' => 'გაუქმებული',
        'completed' => 'დასრულებული',
    ];

    public const RESPONSE_STATUSES = [
        'going' => 'მოვალთ',
        'maybe' => 'ჯერ არ ვიცით',
        'not_going' => 'ვერ მოვალთ',
    ];

    protected $fillable = [
        'kindergarten_group_id',
        'created_by_user_id',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'capacity',
        'status',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KindergartenGroup::class, 'kindergarten_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ClubEventResponse::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at');
    }

    public function scopeVisibleToGroups(Builder $query, array $groupIds): Builder
    {
        return $query->where(function (Builder $visibility) use ($groupIds): void {
            $visibility->whereNull('kindergarten_group_id');
            if ($groupIds !== []) {
                $visibility->orWhereIn('kindergarten_group_id', $groupIds);
            }
        });
    }

    public function audienceLabel(): string
    {
        return $this->group?->name ?? 'ყველა მშობელი';
    }
}
