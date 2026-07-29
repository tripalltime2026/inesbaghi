<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use Notifiable;
    protected $fillable = ['name', 'phone', 'email', 'role', 'status', 'phone_verified_at'];
    protected $hidden = ['remember_token'];
    protected function casts(): array { return ['phone_verified_at' => 'datetime']; }
    public function hasRole(string ...$roles): bool
    {
        return $this->status === 'active' && in_array($this->role, $roles, true);
    }
}
