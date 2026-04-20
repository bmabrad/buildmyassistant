<?php

namespace App\Jobs;

use App\Actions\DeliverLaunchpadOutput;
use App\Actions\GenerateLaunchpadOutput;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateLaunchpadOutputJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(public Lead $lead) {}

    public function handle(GenerateLaunchpadOutput $generate, DeliverLaunchpadOutput $deliver): void
    {
        $lead = $this->lead->fresh();
        $generation = $generate($lead);

        if (in_array($generation->status, ['success', 'fallback'], true)) {
            $deliver($lead->fresh(), $generation);
        }
    }
}
