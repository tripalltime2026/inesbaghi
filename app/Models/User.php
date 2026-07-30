<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'phone', 'email', 'role', 'status', 'phone_verified_at'];
    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return ['phone_verified_at' => 'datetime'];
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->status === 'active' && in_array($this->role, $roles, true);
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

    public function canAccessParentClub(): bool
    {
        return $this->status === 'active'
            && $this->phone_verified_at !== null
            && $this->hasLinkedChild()
            && $this->hasActiveEnrollment();
    }

    public function membershipLabel(): string
    {
        if ($this->canAccessParentClub()) {
            return 'დადასტურებული მშობელი';
        }

        if ($this->hasLinkedChild()) {
            return 'ბავშვთან დაკავშირებული მომხმარებელი';
        }

        return 'რეგისტრირებული მომხმარებელი';
    }
}
