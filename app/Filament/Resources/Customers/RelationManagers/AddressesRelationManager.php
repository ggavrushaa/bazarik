<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Адреси доставки';

    protected static ?string $modelLabel = 'адреса';

    protected static ?string $pluralModelLabel = 'адреси';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('city')
            ->paginated(false)
            ->columns([
                TextColumn::make('recipient_name')->label('Отримувач')->description(fn ($record): string => $record->recipient_phone),
                TextColumn::make('delivery_method')->label('Спосіб'),
                TextColumn::make('city')->label('Місто'),
                TextColumn::make('warehouse')
                    ->label('Відділення / адреса')
                    ->state(fn ($record): ?string => $record->warehouse
                        ?? collect([$record->street, $record->building, $record->apartment ? 'кв. '.$record->apartment : null])->filter()->implode(', '))
                    ->placeholder('—')
                    ->wrap(),
                IconColumn::make('is_default')->label('Основна')->boolean(),
            ]);
    }
}
