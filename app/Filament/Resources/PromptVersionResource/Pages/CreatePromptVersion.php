<?php

namespace App\Filament\Resources\PromptVersionResource\Pages;

use App\Filament\Resources\PromptVersionResource;
use App\Models\PromptVersion;
use Filament\Resources\Pages\CreateRecord;

class CreatePromptVersion extends CreateRecord
{
    protected static string $resource = PromptVersionResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->is_active) {
            PromptVersion::where('product', $this->record->product)
                ->where('id', '!=', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
}
