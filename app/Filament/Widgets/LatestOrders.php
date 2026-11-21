<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Останні замовлення')
            ->query(Order::query()->latest()->limit(10))
            ->paginated(false)
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('Номер')->weight('bold'),
                TextColumn::make('created_at')->label('Дата')->since()->dateTimeTooltip('d.m.Y H:i'),
                TextColumn::make('first_name')
                    ->label('Покупець')
                    ->state(fn (Order $record): string => OrderResource::recipientName($record))
                    ->description(fn (Order $record): string => $record->phone),
                TextColumn::make('total')->label('Сума')->formatStateUsing(fn ($state): string => money($state))->alignEnd(),
                TextColumn::make('status')->label('Статус')->badge(),
                IconColumn::make('is_quick_buy')
                    ->label('1 клік')
                    ->state(fn (Order $record): ?bool => $record->is_quick_buy ?: null)
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->trueColor('primary'),
            ])
            ->headerActions([
                Action::make('all')
                    ->label('Усі замовлення')
                    ->link()
                    ->url(OrderResource::getUrl('index')),
            ]);
    }
}
