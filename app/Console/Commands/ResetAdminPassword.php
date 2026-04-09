<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password
                            {email? : The admin email address}
                            {--password= : New password (omit to generate a random one)}';

    protected $description = 'Reset the password for an admin user';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Admin email address');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");

            return self::FAILURE;
        }

        if (! $user->is_admin) {
            $this->error("User {$email} is not an admin. Refusing to reset password.");

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

        $this->info("Password reset for {$user->email}");

        if ($generated) {
            $this->newLine();
            $this->warn('Generated password (copy it now — it will not be shown again):');
            $this->line("  {$password}");
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
