<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubEventResponse extends Model
{
    protected $fillable = ['club_event_id', 'user_id', 'status', 'note'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(ClubEvent::class, 'club_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
