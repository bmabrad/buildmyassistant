<?php

namespace App\Filament\Resources\LaunchpadTaskResource\Pages;

use App\Filament\Resources\LaunchpadTaskResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewLaunchpadTask extends ViewRecord
{
    protected static string $resource = LaunchpadTaskResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Task Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Buyer Name'),
                        TextEntry::make('email')->label('Buyer Email'),
                        TextEntry::make('token')
                            ->label('Token')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'active' => 'info',
                                'completed' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('phase'),
                        TextEntry::make('phase_1_complete')
                            ->label('Phase 1 Complete')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                        TextEntry::make('created_at')->dateTime('M j, Y g:ia'),
                        TextEntry::make('updated_at')->dateTime('M j, Y g:ia'),
                        TextEntry::make('token')
                            ->label('Chat URL')
                            ->formatStateUsing(fn (string $state): string => url("/launchpad/{$state}"))
                            ->url(fn (string $state): string => url("/launchpad/{$state}"))
                            ->openUrlInNewTab(),
                    ]),
                Section::make('Conversation')
                    ->schema([
                        ViewEntry::make('messages')
                            ->view('filament.infolists.entries.chat-messages'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
