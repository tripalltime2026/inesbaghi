<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubPoll extends Model
{
    public const STATUSES = [
        'draft' => 'მონახაზი',
        'published' => 'გამოქვეყნებული',
        'closed' => 'დახურული',
    ];

    protected $fillable = [
        'kindergarten_group_id',
        'created_by_user_id',
        'question',
        'description',
        'status',
        'closes_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'closes_at' => 'datetime',
            'published_at' => 'datetime',
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

    public function options(): HasMany
    {
        return $this->hasMany(ClubPollOption::class)->orderBy('position');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ClubPollVote::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at');
    }

    public function isOpen(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && ($this->closes_at === null || $this->closes_at->isFuture());
    }
}
