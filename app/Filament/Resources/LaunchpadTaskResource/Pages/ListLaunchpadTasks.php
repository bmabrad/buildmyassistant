<?php

namespace App\Filament\Resources\LaunchpadTaskResource\Pages;

use App\Filament\Resources\LaunchpadTaskResource;
use App\Filament\Widgets\LaunchpadStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListLaunchpadTasks extends ListRecords
{
    protected static string $resource = LaunchpadTaskResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LaunchpadStatsWidget::class,
        ];
    }
}
