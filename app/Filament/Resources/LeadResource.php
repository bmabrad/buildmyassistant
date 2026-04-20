<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $modelLabel = 'Lead';

    protected static ?int $navigationSort = 10;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Buyer')
                    ->schema([
                        TextEntry::make('buyer_name'),
                        TextEntry::make('buyer_email'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('source'),
                        TextEntry::make('created_at')->dateTime('M j, Y g:ia'),
                        TextEntry::make('completed_at')->dateTime('M j, Y g:ia')->placeholder('Not yet'),
                        TextEntry::make('generated_at')->dateTime('M j, Y g:ia')->placeholder('Not yet'),
                        TextEntry::make('delivered_at')->dateTime('M j, Y g:ia')->placeholder('Not yet'),
                    ])
                    ->columns(2),
                Section::make('Chat answers')
                    ->schema([
                        TextEntry::make('business_description')->columnSpanFull(),
                        TextEntry::make('business_role'),
                        TextEntry::make('who_they_serve')->columnSpanFull(),
                        TextEntry::make('tools_used'),
                        TextEntry::make('ai_usage_level'),
                        TextEntry::make('time_drains')->columnSpanFull(),
                        TextEntry::make('tedious_work')->columnSpanFull(),
                        TextEntry::make('one_handoff_today')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Launchpad pick')
                    ->schema([
                        TextEntry::make('business_type')->placeholder('Not classified'),
                        TextEntry::make('assistant_pick')->placeholder('Not picked'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('buyer_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('buyer_email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('business_role')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('tools_used')->sortable(),
                Tables\Columns\TextColumn::make('business_type')->sortable()->placeholder('\u2014'),
                Tables\Columns\TextColumn::make('assistant_pick')->sortable()->placeholder('\u2014'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'partial' => 'warning',
                        'completed' => 'info',
                        'generated' => 'primary',
                        'delivered' => 'success',
                        'duplicate' => 'gray',
                        'failed' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, Y g:ia')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'partial' => 'Partial',
                        'completed' => 'Completed',
                        'generated' => 'Generated',
                        'delivered' => 'Delivered',
                        'duplicate' => 'Duplicate',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options(['launchpad' => 'Launchpad', 'builder' => 'Builder']),
                Tables\Filters\SelectFilter::make('tools_used')
                    ->options([
                        'Claude CoWork' => 'Claude CoWork',
                        'Claude' => 'Claude',
                        'ChatGPT' => 'ChatGPT',
                        'Gemini' => 'Gemini',
                        'Copilot' => 'Copilot',
                        'None yet' => 'None yet',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'view' => Pages\ViewLead::route('/{record}'),
        ];
    }
}
