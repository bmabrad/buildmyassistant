<?php

namespace App\Jobs;

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

    public function handle(GenerateLaunchpadOutput $action): void
    {
        $action($this->lead->fresh());
    }
}
