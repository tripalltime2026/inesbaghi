<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumTopic extends Model
{
    public const CATEGORIES = [
        'general' => 'ზოგადი',
        'nutrition' => 'კვება და ჯანმრთელობა',
        'development' => 'აღზრდა და განვითარება',
        'kindergarten' => 'კითხვები ბაღს',
    ];

    public const STATUSES = [
        'open' => 'პასუხის მოლოდინში',
        'answered' => 'პასუხგაცემული',
        'closed' => 'დახურული',
    ];

    public const PRIORITIES = [
        'normal' => 'ჩვეულებრივი',
        'important' => 'მნიშვნელოვანი',
        'urgent' => 'სასწრაფო',
    ];

    protected $fillable = [
        'kindergarten_group_id',
        'user_id',
        'category',
        'title',
        'body',
        'is_locked',
        'status',
        'priority',
        'is_pinned',
        'answered_by_user_id',
        'answered_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_pinned' => 'boolean',
            'answered_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KindergartenGroup::class, 'kindergarten_group_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class);
    }

    public function officialAnswers(): HasMany
    {
        return $this->comments()->where('is_official_answer', true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
