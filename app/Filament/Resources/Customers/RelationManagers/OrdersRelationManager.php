<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Замовлення';

    protected static ?string $modelLabel = 'замовлення';

    protected static ?string $pluralModelLabel = 'замовлення';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('Номер')->weight('bold'),
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i'),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('payment_status')->label('Оплата')->badge(),
                TextColumn::make('city')->label('Місто')->placeholder('—'),
                TextColumn::make('total')->label('Сума')->formatStateUsing(fn ($state): string => money($state))->alignEnd(),
            ]);
    }
}
