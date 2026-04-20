<?php

namespace App\Filament\Resources;

use App\Actions\DeliverLaunchpadOutput;
use App\Filament\Resources\DeliveryResource\Pages;
use App\Models\Delivery;
use App\Models\Generation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Deliveries';

    protected static ?string $modelLabel = 'Delivery';

    protected static ?int $navigationSort = 25;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Delivery')
                ->schema([
                    TextEntry::make('id')->label('Delivery ID'),
                    TextEntry::make('lead.buyer_email')->label('Lead'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('channel'),
                    TextEntry::make('provider'),
                    TextEntry::make('provider_message_id')->label('Provider message ID')->placeholder('—'),
                    TextEntry::make('subject')->columnSpanFull(),
                    TextEntry::make('to_email'),
                    TextEntry::make('sent_at')->dateTime('M j, Y g:ia')->placeholder('Not sent'),
                    TextEntry::make('created_at')->dateTime('M j, Y g:ia'),
                ])
                ->columns(2),
            Section::make('Error')
                ->schema([TextEntry::make('error')->columnSpanFull()])
                ->visible(fn (Delivery $record) => filled($record->error)),
            Section::make('Attachments')
                ->schema([
                    TextEntry::make('attachments')
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) $state)
                        ->columnSpanFull(),
                ])
                ->collapsed()
                ->visible(fn (Delivery $record) => ! empty($record->attachments)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationMode(Tables\Enums\PaginationMode::Simple)
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('to_email')->label('To')->searchable(),
                Tables\Columns\TextColumn::make('subject')->limit(48)->toggleable(),
                Tables\Columns\TextColumn::make('provider')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sent_at')->dateTime('M j, g:ia')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, g:ia')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (Delivery $record): bool => self::firstPdfPath($record) !== null && is_file(self::firstPdfPath($record)))
                    ->action(function (Delivery $record) {
                        $path = self::firstPdfPath($record);
                        $filename = self::firstPdfFilename($record) ?? 'launchpad.pdf';
                        if ($path === null || ! is_file($path)) {
                            Notification::make()->title('PDF not found on disk.')->danger()->send();

                            return null;
                        }

                        return response()->download($path, $filename);
                    }),
                Action::make('retry')
                    ->label('Retry delivery')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Delivery $record): bool => $record->status === 'failed')
                    ->action(function (Delivery $record) {
                        $lead = $record->lead;
                        if (! $lead) {
                            Notification::make()->title('Delivery has no lead.')->danger()->send();

                            return;
                        }
                        $generation = Generation::where('lead_id', $lead->id)
                            ->whereIn('status', ['success', 'fallback'])
                            ->whereNotNull('output')
                            ->latest('id')
                            ->first();
                        if (! $generation) {
                            Notification::make()->title('No successful generation found for this lead.')->danger()->send();

                            return;
                        }
                        app(DeliverLaunchpadOutput::class)($lead->fresh(), $generation);
                        Notification::make()->title('Retry dispatched.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'view' => Pages\ViewDelivery::route('/{record}'),
        ];
    }

    public static function firstPdfPath(Delivery $delivery): ?string
    {
        $attachments = $delivery->attachments;
        if (! is_array($attachments) || empty($attachments)) {
            return null;
        }

        return $attachments[0]['path'] ?? null;
    }

    public static function firstPdfFilename(Delivery $delivery): ?string
    {
        $attachments = $delivery->attachments;
        if (! is_array($attachments) || empty($attachments)) {
            return null;
        }

        return $attachments[0]['filename'] ?? null;
    }
}
