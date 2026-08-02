<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'username', 'password', 'phone', 'email', 'role', 'status',
        'phone_verified_at', 'email_verified_at',
        'club_access_approved_at', 'club_access_approved_by_user_id',
        'payment_due', 'payment_paid', 'payment_due_at', 'payment_note',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'club_access_approved_at' => 'datetime',
            'payment_due' => 'decimal:2',
            'payment_paid' => 'decimal:2',
            'payment_due_at' => 'date',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->status === 'active' && in_array($this->role, $roles, true);
    }

    public function hasVerifiedIdentity(): bool
    {
        return (filled($this->username) && filled($this->password))
            || $this->phone_verified_at !== null
            || $this->email_verified_at !== null;
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_guardians')
            ->withPivot(['relationship', 'is_primary', 'can_pick_up'])
            ->withTimestamps();
    }

    public function hasLinkedChild(): bool
    {
        return $this->children()->exists();
    }

    public function hasActiveEnrollment(): bool
    {
        return $this->children()
            ->whereHas('enrollments', fn ($query) => $query
                ->where('status', 'active')
                ->whereHas('group', fn ($groupQuery) => $groupQuery->where('is_active', true)))
            ->exists();
    }

    public function isClubAccessApproved(): bool
    {
        return $this->club_access_approved_at !== null;
    }

    public function canAccessParentClub(): bool
    {
        return $this->status === 'active'
            && $this->isClubAccessApproved()
            && $this->hasVerifiedIdentity()
            && $this->hasLinkedChild()
            && $this->hasActiveEnrollment();
    }

    public function paymentOutstanding(): float
    {
        return max(0, (float) $this->payment_due - (float) $this->payment_paid);
    }

    public function membershipLabel(): string
    {
        if ($this->canAccessParentClub()) {
            return 'დადასტურებული მშობელი';
        }

        if ($this->isClubAccessApproved()) {
            return 'წვდომა დამტკიცებულია — ჩარიცხვა მოსაწესრიგებელია';
        }

        return 'ადმინისტრატორის დადასტურებას ელოდება';
    }
}
