<?php

namespace App\Filament\Resources\LaunchpadTaskResource\Pages;

use App\Filament\Resources\LaunchpadTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaunchpadTask extends EditRecord
{
    protected static string $resource = LaunchpadTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
