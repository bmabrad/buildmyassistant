<?php

namespace App\Filament\Widgets;

use App\Models\Delivery;
use App\Models\Generation;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LaunchpadStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Launchpad';

    protected function getStats(): array
    {
        $totalLeads = Lead::count();
        $completed = Lead::where('status', 'completed')->count();
        $generated = Lead::where('status', 'generated')->count();
        $delivered = Lead::where('status', 'delivered')->count();
        $duplicate = Lead::where('status', 'duplicate')->count();

        $generations = Generation::count();
        $success = Generation::whereIn('status', ['success', 'fallback'])->count();
        $successRate = $generations > 0
            ? round($success / $generations * 100)
            : null;

        $deliveries = Delivery::count();
        $sent = Delivery::where('status', 'sent')->count();

        return [
            Stat::make('Total leads', $totalLeads)
                ->description("$completed completed, $duplicate duplicate"),
            Stat::make('Generated', $generated + $delivered)
                ->description($successRate !== null ? "{$successRate}% generation success" : 'No generations yet')
                ->color($successRate !== null && $successRate >= 95 ? 'success' : ($successRate !== null ? 'warning' : 'gray')),
            Stat::make('Delivered', $delivered)
                ->description("$sent / $deliveries emails sent")
                ->color($delivered > 0 ? 'success' : 'gray'),
        ];
    }
}
