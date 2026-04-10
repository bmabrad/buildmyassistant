<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPassword extends Command
{
    protected $signature = 'password:reset
                            {email? : The user email address}
                            {--password= : New password (omit to generate a random one)}';

    protected $description = 'Reset the password for a user';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('User email address');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");

            return self::FAILURE;
        }

        $password = $this->option('password');
        $generated = false;

        if (! $password) {
            $password = Str::password(length: 20, symbols: false);
            $generated = true;
        }

        $user->password = Hash::make($password);
        $user->save();

        $label = $user->is_admin ? 'admin' : 'user';
        $this->info("Password reset for {$label} {$user->email}");

        if ($generated) {
            $this->newLine();
            $this->warn('Generated password (copy it now — it will not be shown again):');
            $this->line("  {$password}");
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
