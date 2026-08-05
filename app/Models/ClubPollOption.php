<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubPollOption extends Model
{
    protected $fillable = [
        'club_poll_id',
        'label',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(ClubPoll::class, 'club_poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ClubPollVote::class);
    }
}
