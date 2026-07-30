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

    public function hasVerifiedParentAccess(): bool
    {
        if ($this->status !== 'active' || $this->role !== 'parent' || $this->phone_verified_at === null) {
            return false;
        }

        return $this->children()
            ->whereHas('enrollments', fn ($enrollments) => $enrollments
                ->where('status', 'active')
                ->whereHas('group', fn ($groups) => $groups->where('is_active', true)))
            ->exists();
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_guardians')
            ->withPivot(['relationship', 'is_primary', 'can_pick_up'])
            ->withTimestamps();
    }
}
