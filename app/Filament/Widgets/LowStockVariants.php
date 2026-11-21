<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Варіанти, що от-от закінчаться: активні, залишок ≤ 3 */
class LowStockVariants extends TableWidget
{
    public const THRESHOLD = 3;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Закінчуються на складі')
            ->description('Активні варіанти, яких лишилось '.self::THRESHOLD.' шт або менше.')
            ->query(
                ProductVariant::query()
                    ->with('product')
                    ->where('is_active', true)
                    ->where('stock', '<=', self::THRESHOLD)
                    ->whereHas('product', fn (Builder $q) => $q->where('is_active', true))
            )
            ->defaultSort('stock')
            ->defaultPaginationPageOption(10)
            ->recordUrl(fn (ProductVariant $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id]))
            ->emptyStateHeading('Усього вистачає')
            ->emptyStateDescription('Немає активних варіантів із малим залишком.')
            ->columns([
                TextColumn::make('product.name')->label('Товар')->limit(50)->searchable(),
                TextColumn::make('label')->label('Варіант')->state(fn (ProductVariant $record): ?string => $record->label)->placeholder('—'),
                TextColumn::make('sku')->label('Артикул')->searchable(),
                TextColumn::make('stock')
                    ->label('Залишок')
                    ->suffix(' шт')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'warning')
                    ->sortable(),
            ]);
    }
}
