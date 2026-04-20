<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromptVersionResource\Pages;
use App\Models\PromptVersion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PromptVersionResource extends Resource
{
    protected static ?string $model = PromptVersion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Prompt versions';

    protected static ?string $modelLabel = 'Prompt version';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('product')
                ->options(['launchpad' => 'Launchpad', 'builder' => 'Builder'])
                ->required(),
            TextInput::make('name')->required()->maxLength(120),
            Toggle::make('is_active')->label('Active')
                ->helperText('Only one version per product should be active. Flipping this on will deactivate siblings.'),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
            Textarea::make('system_prompt')->rows(20)->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('product')->badge(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('M j, g:ia')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->options(['launchpad' => 'Launchpad', 'builder' => 'Builder']),
            ])
            ->actions([
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (PromptVersion $record) => $record->is_active)
                    ->action(function (PromptVersion $record) {
                        PromptVersion::where('product', $record->product)->update(['is_active' => false]);
                        $record->update(['is_active' => true]);
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromptVersions::route('/'),
            'create' => Pages\CreatePromptVersion::route('/create'),
            'edit' => Pages\EditPromptVersion::route('/{record}/edit'),
        ];
    }
}
