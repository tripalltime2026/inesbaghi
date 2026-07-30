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

    protected $fillable = [
        'kindergarten_group_id',
        'user_id',
        'category',
        'title',
        'body',
        'is_locked',
    ];

    protected function casts(): array
    {
        return ['is_locked' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(KindergartenGroup::class, 'kindergarten_group_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class);
    }
}
