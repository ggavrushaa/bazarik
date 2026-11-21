<?php

namespace App\Filament\Resources\Customers;

use App\Enums\OrderStatus;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Покупці з акаунтом (вхід за телефоном). Гостьові замовлення — лише в «Замовленнях». */
class CustomerResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Покупці';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'покупець';

    protected static ?string $pluralModelLabel = 'покупці';

    protected static ?string $navigationLabel = 'Покупці';

    protected static ?string $recordTitleAttribute = 'phone';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getGloballySearchableAttributes(): array
    {
        return ['phone', 'first_name', 'last_name', 'email'];
    }

    public static function canCreate(): bool
    {
        return false; // акаунт створюється, коли покупець входить за номером
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 2, 'lg' => 4])
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name')->label("Ім'я"),
                    TextEntry::make('phone')->label('Телефон')->copyable()->url(fn (Customer $record): string => 'tel:'.$record->phone),
                    TextEntry::make('email')->label('Email')->placeholder('—'),
                    TextEntry::make('created_at')->label('З нами з')->date('d.m.Y'),
                    TextEntry::make('orders_count')->label('Замовлень')
                        ->state(fn (Customer $record): int => $record->orders()->count()),
                    TextEntry::make('orders_sum')->label('Сума отриманих')
                        ->state(fn (Customer $record): string => money($record->orders()->where('status', OrderStatus::Completed)->sum('total'))),
                    TextEntry::make('favorites_count')->label('В обраному')
                        ->state(fn (Customer $record): int => $record->favorites()->count()),
                    TextEntry::make('phone_verified_at')->label('Телефон підтверджено')->dateTime('d.m.Y H:i')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('orders')->withMax('orders', 'created_at'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('first_name')
                    ->label("Ім'я")
                    ->state(fn (Customer $record): string => trim("{$record->first_name} {$record->last_name}") ?: '—')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orders_count')
                    ->label('Замовлень')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('orders_max_created_at')
                    ->label('Останнє замовлення')
                    ->dateTime('d.m.Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Зареєструвався')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('has_orders')
                    ->label('Замовлення')
                    ->trueLabel('Є замовлення')
                    ->falseLabel('Без замовлень')
                    ->queries(
                        true: fn (Builder $query) => $query->has('orders'),
                        false: fn (Builder $query) => $query->doesntHave('orders'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            AddressesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
