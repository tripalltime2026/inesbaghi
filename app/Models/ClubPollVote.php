<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubPollVote extends Model
{
    protected $fillable = [
        'club_poll_id',
        'club_poll_option_id',
        'user_id',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(ClubPoll::class, 'club_poll_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ClubPollOption::class, 'club_poll_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
