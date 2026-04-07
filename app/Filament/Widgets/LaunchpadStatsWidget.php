<?php

namespace App\Filament\Widgets;

use App\Models\Assistant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class LaunchpadStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalTasks = Assistant::count();
        $completedTasks = Assistant::completed()->count();
        $tasksThisWeek = Assistant::where('created_at', '>=', Carbon::now()->startOfWeek())->count();
        $tasksThisMonth = Assistant::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 1)
            : 0;

        $phase1CompleteTasks = Assistant::where('phase_1_complete', true)->count();
        $phase2Tasks = Assistant::where('phase', 2)->count();
        $phase2Rate = $phase1CompleteTasks > 0
            ? round(($phase2Tasks / $phase1CompleteTasks) * 100, 1)
            : 0;

        return [
            Stat::make('Total tasks', $totalTasks),
            Stat::make('Total revenue', '$' . ($totalTasks * 5) . ' AUD'),
            Stat::make('Completion rate', $completionRate . '%'),
            Stat::make('Tasks this week', $tasksThisWeek),
            Stat::make('Tasks this month', $tasksThisMonth),
            Stat::make('Phase 2 rate', $phase2Rate . '%'),
        ];
    }
}
