<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdmin extends Command
{
    protected $signature = 'athar:make-admin {email : Email address of an existing user}';

    protected $description = 'Grant administrator access to an existing user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user was found with that email address.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => 'admin'])->save();
        $this->info("Administrator access granted to {$user->email}.");

        return self::SUCCESS;
    }
}
