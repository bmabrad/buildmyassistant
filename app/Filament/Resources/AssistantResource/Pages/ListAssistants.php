<?php

namespace App\Filament\Resources\AssistantResource\Pages;

use App\Filament\Resources\AssistantResource;
use App\Filament\Widgets\LaunchpadStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListAssistants extends ListRecords
{
    protected static string $resource = AssistantResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LaunchpadStatsWidget::class,
        ];
    }
}
