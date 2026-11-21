<?php

namespace App\Filament\Resources\Orders;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Filament\Support\OrderWorkflow;
use App\Models\Order;
use App\Models\OrderItem;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use UnitEnum;

class OrderResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Продажі';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'замовлення';

    protected static ?string $pluralModelLabel = 'замовлення';

    protected static ?string $navigationLabel = 'Замовлення';

    protected static ?string $recordTitleAttribute = 'number';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->where('status', OrderStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Нові замовлення';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'phone', 'ttn', 'last_name'];
    }

    public static function canCreate(): bool
    {
        return false; // замовлення оформлюють лише на вітрині
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Товари')
                    ->columnSpanFull()
                    ->description('Знімок на момент оформлення: назва, ціна й варіант не змінюються разом із каталогом.')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Товар'),
                                TableColumn::make('Артикул'),
                                TableColumn::make('Ціна')->alignment(Alignment::End),
                                TableColumn::make('К-сть')->alignment(Alignment::End),
                                TableColumn::make('Сума')->alignment(Alignment::End),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->state(fn (OrderItem $record): string => $record->name.($record->variant_label ? ' · '.$record->variant_label : ''))
                                    ->url(fn (OrderItem $record): ?string => $record->product_id
                                        ? ProductResource::getUrl('edit', ['record' => $record->product_id])
                                        : null),
                                TextEntry::make('sku')->hiddenLabel()->placeholder('—'),
                                TextEntry::make('price')->hiddenLabel()->formatStateUsing(fn ($state): string => money($state))->alignEnd(),
                                TextEntry::make('quantity')->hiddenLabel()->suffix(' шт')->alignEnd(),
                                TextEntry::make('total')->hiddenLabel()->formatStateUsing(fn ($state): string => money($state))->weight('bold')->alignEnd(),
                            ]),
                    ]),

                Group::make([
                    Section::make('Покупець')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('recipient')
                                ->label('Отримувач')
                                ->state(fn (Order $record): string => self::recipientName($record)),
                            TextEntry::make('phone')
                                ->label('Телефон')
                                ->copyable()
                                ->url(fn (Order $record): string => 'tel:'.$record->phone),
                            TextEntry::make('email')
                                ->label('Email')
                                ->placeholder('—'),
                            TextEntry::make('customer.phone')
                                ->label('Акаунт')
                                ->placeholder('Гість')
                                ->state(fn (Order $record): ?string => $record->customer?->name)
                                ->url(fn (Order $record): ?string => $record->customer_id
                                    ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                                    : null),
                            TextEntry::make('comment')
                                ->label('Коментар покупця')
                                ->placeholder('—')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Доставка')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('delivery_method')
                                ->label('Спосіб')
                                ->placeholder('—'),
                            TextEntry::make('city')
                                ->label('Місто')
                                ->placeholder('—'),
                            TextEntry::make('address')
                                ->label('Відділення / адреса')
                                ->state(fn (Order $record): ?string => self::deliveryAddress($record))
                                ->placeholder('—')
                                ->columnSpanFull(),
                            TextInput::make('ttn')
                                ->label('ТТН Нової Пошти')
                                ->regex('/^\d{14}$/')
                                ->validationMessages(['regex' => 'ТТН Нової Пошти — 14 цифр.'])
                                ->required(fn (Get $get): bool => in_array(self::status($get('status')), [OrderStatus::Shipped, OrderStatus::Arrived], true))
                                ->validationAttribute('ТТН'),
                            TextEntry::make('np_status')
                                ->label('Статус у НП')
                                ->placeholder('—'),
                        ]),

                    Section::make('Оплата')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('payment_method')
                                ->label('Спосіб оплати')
                                ->placeholder('—'),
                            Select::make('payment_status')
                                ->label('Статус оплати')
                                ->options(PaymentStatus::class)
                                ->required()
                                ->native(false),
                            TextEntry::make('subtotal')
                                ->label('Товари')
                                ->formatStateUsing(fn ($state): string => money($state)),
                            TextEntry::make('discount')
                                ->label('Знижка')
                                ->formatStateUsing(fn ($state): string => (float) $state > 0 ? '−'.money($state) : '—'),
                            TextEntry::make('delivery_cost')
                                ->label('Доставка')
                                ->formatStateUsing(fn ($state): string => (float) $state > 0 ? money($state) : 'за тарифом НП'),
                            TextEntry::make('total')
                                ->label('До сплати')
                                ->weight('bold')
                                ->formatStateUsing(fn ($state): string => money($state)),
                            TextEntry::make('paid_at')
                                ->label('Оплачено')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('—'),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Обробка')
                        ->schema([
                            Select::make('status')
                                ->label('Статус')
                                ->options(OrderStatus::class)
                                ->required()
                                ->native(false)
                                ->live()
                                ->disabled(fn (?Order $record): bool => $record && in_array($record->status, OrderWorkflow::RESTOCKED, true))
                                ->helperText(fn (?Order $record): ?string => $record && in_array($record->status, OrderWorkflow::RESTOCKED, true)
                                    ? 'Товари вже повернули на склад — статус не змінюється.'
                                    : 'Під час скасування чи повернення товари повертаються на склад.'),
                            Toggle::make('dont_call')
                                ->label('Не дзвонити для підтвердження'),
                            Textarea::make('manager_note')
                                ->label('Нотатка менеджера')
                                ->helperText('Бачить лише команда магазину.')
                                ->rows(4),
                        ]),

                    Section::make('Дати')
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('Оформлене')
                                ->dateTime('d.m.Y H:i'),
                            TextEntry::make('shipped_at')
                                ->label('Відправлене')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('—'),
                            TextEntry::make('delivered_at')
                                ->label('Отримане')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('—'),
                            TextEntry::make('is_quick_buy')
                                ->label('Джерело')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Купити в 1 клік' : 'Кошик'),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Номер')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('Покупець')
                    ->state(fn (Order $record): string => self::recipientName($record))
                    ->description(fn (Order $record): string => $record->phone)
                    ->searchable(['first_name', 'last_name', 'phone']),
                TextColumn::make('total')
                    ->label('Сума')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('payment_method')
                    ->label('Оплата')
                    ->formatStateUsing(fn (?PaymentMethod $state): string => match ($state) {
                        PaymentMethod::CashOnDelivery => 'Післяплата',
                        PaymentMethod::Online => 'Онлайн',
                        default => '—',
                    })
                    ->description(fn (Order $record): ?string => $record->payment_status?->getLabel()),
                IconColumn::make('is_quick_buy')
                    ->label('1 клік')
                    ->state(fn (Order $record): ?bool => $record->is_quick_buy ?: null)
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedBolt)
                    ->trueColor('primary')
                    ->tooltip('Купити в 1 клік')
                    ->alignCenter(),
                IconColumn::make('dont_call')
                    ->label('Не дзвонити')
                    ->state(fn (Order $record): ?bool => $record->dont_call ?: null)
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedPhoneXMark)
                    ->trueColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ttn')
                    ->label('ТТН')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('Місто')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(OrderStatus::class)
                    ->multiple(),
                SelectFilter::make('payment_method')
                    ->label('Спосіб оплати')
                    ->options(PaymentMethod::class),
                SelectFilter::make('payment_status')
                    ->label('Статус оплати')
                    ->options(PaymentStatus::class),
                TernaryFilter::make('is_quick_buy')
                    ->label('Купити в 1 клік')
                    ->trueLabel('Лише в 1 клік')
                    ->falseLabel('Лише з кошика'),
                Filter::make('created_at')
                    ->label('Дата')
                    ->schema([
                        DatePicker::make('from')->label('З')->native(false)->displayFormat('d.m.Y'),
                        DatePicker::make('until')->label('По')->native(false)->displayFormat('d.m.Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Indicator::make('З '.Carbon::parse($data['from'])->format('d.m.Y'))->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Indicator::make('По '.Carbon::parse($data['until'])->format('d.m.Y'))->removeField('until');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Відкрити'),
                ActionGroup::make(OrderActions::all()),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}'),
        ];
    }

    public static function recipientName(Order $order): string
    {
        return trim("{$order->first_name} {$order->last_name}") ?: '—';
    }

    public static function deliveryAddress(Order $order): ?string
    {
        if ($order->delivery_method === DeliveryMethod::NovaPoshtaCourier) {
            $line = trim(collect([$order->street, $order->building])->filter()->implode(', '));

            return $line === '' ? null : $line.($order->apartment ? ', кв. '.$order->apartment : '');
        }

        return $order->warehouse;
    }

    public static function status(mixed $state): ?OrderStatus
    {
        return $state instanceof OrderStatus ? $state : OrderStatus::tryFrom((string) $state);
    }
}
