<?php

namespace App\Filament\Resources\StockNotifications;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\StockNotifications\Pages\ListStockNotifications;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Models\StockNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/** Запити «Повідомити мене, коли з’явиться» */
class StockNotificationResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = StockNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Покупці';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'запит про наявність';

    protected static ?string $pluralModelLabel = 'запити про наявність';

    protected static ?string $navigationLabel = 'Чекають на товар';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationBadge(): ?string
    {
        $count = StockNotification::query()->pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'gray';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product.variants', 'variant']))
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('product.name')
                    ->label('Товар')
                    ->collapsible(),
            ])
            ->defaultGroup('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Товар')
                    ->url(fn (StockNotification $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id]))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('variant.label')
                    ->label('Варіант')
                    ->state(fn (StockNotification $record): ?string => $record->variant?->label)
                    ->placeholder('Будь-який'),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('stock_now')
                    ->label('Зараз на складі')
                    ->state(fn (StockNotification $record): int => $record->variant
                        ? (int) ($record->variant->is_active ? $record->variant->stock : 0)
                        : (int) $record->product?->total_stock)
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->suffix(' шт'),
                TextColumn::make('created_at')
                    ->label('Залишили запит')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('notified_at')
                    ->label('Повідомили')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Ще ні')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('pending')
                    ->label('Статус')
                    ->trueLabel('Чекають')
                    ->falseLabel('Повідомлені')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('notified_at'),
                        false: fn (Builder $query) => $query->whereNotNull('notified_at'),
                    ),
                SelectFilter::make('product')
                    ->label('Товар')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('markNotified')
                    ->label('Повідомили')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (StockNotification $record): bool => $record->notified_at === null)
                    ->action(fn (StockNotification $record) => $record->update(['notified_at' => now()])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markNotified')
                        ->label('Позначити, що повідомили')
                        ->icon(Heroicon::OutlinedCheck)
                        ->action(fn (Collection $records) => StockNotification::query()
                            ->whereKey($records->modelKeys())
                            ->whereNull('notified_at')
                            ->update(['notified_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockNotifications::route('/'),
        ];
    }
}
