<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SetUserCredentials extends Command
{
    protected $signature = 'auth:set-credentials
                            {username : New login name}
                            {password : New password, minimum 8 characters}
                            {--user= : Existing user ID}
                            {--admin : Select the single active administrator}';

    protected $description = 'Set a username and password for an existing account without changing its role';

    public function handle(): int
    {
        $username = Str::of((string) $this->argument('username'))->squish()->lower()->toString();
        $password = (string) $this->argument('password');

        if (mb_strlen($username) < 2 || mb_strlen($username) > 80) {
            $this->error('Username must contain 2 to 80 characters.');
            return self::FAILURE;
        }

        if (mb_strlen($password) < 8) {
            $this->error('Password must contain at least 8 characters.');
            return self::FAILURE;
        }

        $query = User::query()->where('status', 'active');
        if ($this->option('user')) {
            $query->whereKey((int) $this->option('user'));
        } elseif ($this->option('admin')) {
            $query->where('role', 'admin');
        } else {
            $this->error('Use --admin or --user=ID to select an existing account.');
            return self::FAILURE;
        }

        $users = $query->get();
        if ($users->count() !== 1) {
            $this->error('Exactly one active account must match.');
            return self::FAILURE;
        }

        $user = $users->first();
        $usernameOwner = User::query()
            ->where('username', $username)
            ->whereKeyNot($user->id)
            ->exists();

        if ($usernameOwner) {
            $this->error('This username is already used by another account.');
            return self::FAILURE;
        }

        $user->forceFill([
            'username' => $username,
            'password' => Hash::make($password),
        ])->save();

        $this->info("Credentials updated for user #{$user->id}. The role remains {$user->role}.");
        return self::SUCCESS;
    }
}
