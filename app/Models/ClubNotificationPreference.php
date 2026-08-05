<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'event_updates',
        'forum_replies',
        'payment_reminders',
        'weekly_digest',
    ];

    protected function casts(): array
    {
        return [
            'event_updates' => 'boolean',
            'forum_replies' => 'boolean',
            'payment_reminders' => 'boolean',
            'weekly_digest' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
