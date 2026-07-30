<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportConversation extends Model
{
    public const STATUSES = [
        'new' => 'ახალი',
        'ai_active' => 'AI პასუხობს',
        'waiting_admin' => 'ადმინისტრატორის პასუხს ელოდება',
        'in_progress' => 'მიმდინარეობს',
        'resolved' => 'დასრულებული',
        'blocked' => 'დაბლოკილი',
    ];

    protected $fillable = [
        'public_token', 'user_id', 'guest_name', 'guest_phone', 'status', 'mode',
        'assigned_to_user_id', 'topic', 'priority', 'context', 'last_message_at',
        'admin_last_read_at', 'user_last_read_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'last_message_at' => 'datetime',
            'admin_last_read_at' => 'datetime',
            'user_last_read_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function visibleMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    public function isHumanManaged(): bool
    {
        return $this->mode === 'human' || in_array($this->status, ['waiting_admin', 'in_progress'], true);
    }
}
