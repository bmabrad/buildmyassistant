<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GenerationResource\Pages;
use App\Models\Generation;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class GenerationResource extends Resource
{
    protected static ?string $model = Generation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Generations';

    protected static ?string $modelLabel = 'Generation';

    protected static ?int $navigationSort = 20;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Run')
                    ->schema([
                        TextEntry::make('id')->label('Generation ID'),
                        TextEntry::make('lead.buyer_email')->label('Lead'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('model'),
                        TextEntry::make('promptVersion.name')->label('Prompt version')->placeholder('\u2014'),
                        TextEntry::make('retry_of_id')->label('Retry of')->placeholder('\u2014'),
                        TextEntry::make('latency_ms')->label('Latency (ms)')->placeholder('\u2014'),
                        TextEntry::make('input_tokens')->placeholder('\u2014'),
                        TextEntry::make('output_tokens')->placeholder('\u2014'),
                        TextEntry::make('created_at')->dateTime('M j, Y g:ia'),
                    ])
                    ->columns(2),
                Section::make('Error')
                    ->schema([
                        TextEntry::make('error')->placeholder('None')->columnSpanFull(),
                    ])
                    ->visible(fn (Generation $record) => filled($record->error)),
                Section::make('Input payload')
                    ->schema([
                        TextEntry::make('input_payload')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $state)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
                Section::make('Output')
                    ->schema([
                        TextEntry::make('output')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $state)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
                Section::make('Raw response')
                    ->schema([
                        TextEntry::make('raw_response')->placeholder('None')->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn (Generation $record) => filled($record->raw_response)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('lead.buyer_email')->label('Buyer')->searchable(),
                Tables\Columns\TextColumn::make('product'),
                Tables\Columns\TextColumn::make('model')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'fallback' => 'warning',
                        'invalid_json', 'schema_failed', 'failed' => 'danger',
                        'pending' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('latency_ms')->label('Latency (ms)')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('input_tokens')->label('Input tok')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('output_tokens')->label('Output tok')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, g:ia')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'invalid_json' => 'Invalid JSON',
                        'schema_failed' => 'Schema failed',
                        'failed' => 'Failed',
                        'fallback' => 'Fallback',
                    ]),
                Tables\Filters\SelectFilter::make('product')
                    ->options(['launchpad' => 'Launchpad', 'builder' => 'Builder']),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGenerations::route('/'),
            'view' => Pages\ViewGeneration::route('/{record}'),
        ];
    }
}
