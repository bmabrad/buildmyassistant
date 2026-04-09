<?php

namespace App\Console\Commands;

use App\Models\MagicLink;
use Illuminate\Console\Command;

class CleanExpiredMagicLinks extends Command
{
    protected $signature = 'magic-links:clean';

    protected $description = 'Delete magic links older than 24 hours';

    public function handle(): int
    {
        $deleted = MagicLink::where('created_at', '<', now()->subDay())->delete();

        $this->info("Deleted {$deleted} expired magic link(s).");

        return self::SUCCESS;
    }
}
