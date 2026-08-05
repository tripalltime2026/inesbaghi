<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumComment extends Model
{
    protected $fillable = [
        'forum_topic_id',
        'user_id',
        'body',
        'is_official_answer',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'is_official_answer' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'forum_topic_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
