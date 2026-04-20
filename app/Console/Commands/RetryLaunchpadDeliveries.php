<?php

namespace App\Console\Commands;

use App\Actions\DeliverLaunchpadOutput;
use App\Models\Delivery;
use App\Models\Generation;
use Illuminate\Console\Command;

class RetryLaunchpadDeliveries extends Command
{
    protected $signature = 'launchpad:retry-deliveries {--since= : Only retry deliveries failed on or after this date (YYYY-MM-DD)}';

    protected $description = 'Retry all failed Launchpad deliveries. Picks the most recent successful or fallback generation for each lead and dispatches a fresh delivery.';

    public function handle(DeliverLaunchpadOutput $deliver): int
    {
        $query = Delivery::where('status', 'failed')->with('lead');
        if ($since = $this->option('since')) {
            $query->where('created_at', '>=', $since);
        }

        $count = 0;
        $skipped = 0;
        foreach ($query->get() as $delivery) {
            $lead = $delivery->lead;
            if (! $lead) {
                $this->warn("Delivery #{$delivery->id} has no lead. Skipping.");
                $skipped++;

                continue;
            }
            $generation = Generation::where('lead_id', $lead->id)
                ->whereIn('status', ['success', 'fallback'])
                ->whereNotNull('output')
                ->latest('id')
                ->first();
            if (! $generation) {
                $this->warn("Lead #{$lead->id} has no usable generation. Skipping delivery #{$delivery->id}.");
                $skipped++;

                continue;
            }

            $this->info("Retrying delivery #{$delivery->id} for lead #{$lead->id} ({$lead->buyer_email})");
            $deliver($lead->fresh(), $generation);
            $count++;
        }

        $this->line('');
        $this->info("Retried: {$count}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
