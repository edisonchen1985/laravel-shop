<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeFirstAdmin extends Command
{
    protected $signature = 'app:make-first-admin {email : Email address of the existing user to promote}';

    protected $description = 'Grant Admin access to the first administrator account';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->info('This user is already an administrator.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Grant initial Admin access to {$user->email}?", false)) {
            $this->comment('No changes made.');

            return self::FAILURE;
        }

        return DB::transaction(function () use ($user): int {
            // Serialize first-admin setup attempts against a stable existing user row.
            User::query()->orderBy('id')->lockForUpdate()->firstOrFail();

            if (User::query()->where('is_admin', true)->exists()) {
                $this->error('An administrator already exists; first-admin setup is disabled.');

                return self::FAILURE;
            }

            $user->refresh();
            $user->forceFill(['is_admin' => true])->save();

            $this->info("{$user->email} is now an administrator.");

            return self::SUCCESS;
        });
    }
}
