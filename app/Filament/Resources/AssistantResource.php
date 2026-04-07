<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssistantResource\Pages;
use App\Models\Assistant;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssistantResource extends Resource
{
    protected static ?string $model = Assistant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Assistants';

    protected static ?string $modelLabel = 'Assistant';

    protected static ?string $pluralModelLabel = 'Assistants';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25])
            ->paginationMode(Tables\Enums\PaginationMode::Simple)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('phase')
                    ->sortable(),
                Tables\Columns\TextColumn::make('chats_count')
                    ->counts('chats')
                    ->label('Messages'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y g:ia')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Permanently delete')
                    ->modalDescription('This will permanently delete this assistant and all its messages. This cannot be undone.')
                    ->modalSubmitActionLabel('Yes, permanently delete')
                    ->modalCancelActionLabel('No, keep'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssistants::route('/'),
            'view' => Pages\ViewAssistant::route('/{record}'),
            'edit' => Pages\EditAssistant::route('/{record}/edit'),
        ];
    }
}
