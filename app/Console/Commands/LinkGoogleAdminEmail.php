<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LinkGoogleAdminEmail extends Command
{
    protected $signature = 'auth:link-google-admin {email : Verified Google email} {--user= : Existing admin user ID}';

    protected $description = 'Link a verified Google email to an existing active administrator without changing roles';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        $query = User::query()->where('role', 'admin')->where('status', 'active');
        if ($this->option('user')) {
            $query->whereKey((int) $this->option('user'));
        }

        $admins = $query->get();
        if ($admins->count() !== 1) {
            $this->error('Exactly one active administrator must match. Use --user=ID when necessary.');

            return self::FAILURE;
        }

        $admin = $admins->first();
        $emailOwner = User::query()->whereRaw('LOWER(email) = ?', [$email])->whereKeyNot($admin->id)->first();
        if ($emailOwner) {
            $this->error('This email is already used by another account.');

            return self::FAILURE;
        }

        $admin->forceFill(['email' => $email])->save();
        $this->info('Google email linked to the existing administrator. The role was not changed.');

        return self::SUCCESS;
    }
}
