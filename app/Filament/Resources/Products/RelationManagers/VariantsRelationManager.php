<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

/** Варіанти = колір × розмір із залишком на складі */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Варіанти й залишки';

    protected static ?string $modelLabel = 'варіант';

    protected static ?string $pluralModelLabel = 'варіанти';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedSquares2x2;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Product $ownerRecord */
        return (string) $ownerRecord->variants()->where('is_active', true)->sum('stock').' шт';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('sku')
                    ->label('Артикул (SKU)')
                    ->required()
                    ->maxLength(255)
                    ->unique(ProductVariant::class, 'sku', ignoreRecord: true),
                TextInput::make('stock')
                    ->label('Залишок')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->suffix('шт'),
                TextInput::make('color')
                    ->label('Колір')
                    ->placeholder('Оливковий')
                    ->maxLength(255),
                ColorPicker::make('color_hex')
                    ->label('Відтінок для кружечка')
                    ->regex('/^#[0-9a-fA-F]{6}$/'),
                TextInput::make('size')
                    ->label('Розмір')
                    ->placeholder('M')
                    ->maxLength(255)
                    ->unique(
                        ProductVariant::class,
                        'size',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                            ->where('product_id', $this->getOwnerRecord()->getKey())
                            ->where('color', $get('color') ?: null),
                    )
                    ->validationMessages(['unique' => 'Такий колір і розмір у цього товару вже є.']),
                TextInput::make('price')
                    ->label('Своя ціна')
                    ->helperText(fn (): string => 'Порожньо — ціна товару, '.money($this->getOwnerRecord()->price).'.')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('₴'),
                TextInput::make('sort')
                    ->label('Порядок')
                    ->numeric()
                    ->integer()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Продається')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->paginated([25, 50, 'all'])
            ->columns([
                TextColumn::make('sku')
                    ->label('Артикул')
                    ->searchable()
                    ->copyable(),
                ColorColumn::make('color_hex')
                    ->label('')
                    ->width('1%'),
                TextColumn::make('color')
                    ->label('Колір')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('size')
                    ->label('Розмір')
                    ->placeholder('—'),
                TextColumn::make('price')
                    ->label('Ціна')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->placeholder(fn (): string => money($this->getOwnerRecord()->price))
                    ->alignEnd(),
                TextInputColumn::make('stock')
                    ->label('Залишок, шт')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0'])
                    ->width('8rem'),
                ToggleColumn::make('is_active')
                    ->label('Продається'),
            ])
            ->filters([
                TernaryFilter::make('in_stock')
                    ->label('Наявність')
                    ->trueLabel('Є на складі')
                    ->falseLabel('Закінчився')
                    ->queries(
                        true: fn (Builder $query) => $query->where('stock', '>', 0),
                        false: fn (Builder $query) => $query->where('stock', 0),
                    ),
            ])
            ->headerActions([
                CreateAction::make()->label('Додати варіант'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('stock')
                        ->label('Змінити залишок')
                        ->icon(Heroicon::OutlinedCalculator)
                        ->schema([
                            Radio::make('mode')
                                ->label('Що зробити')
                                ->options([
                                    'set' => 'Встановити залишок',
                                    'add' => 'Додати до залишку (прихід)',
                                ])
                                ->default('set')
                                ->required(),
                            TextInput::make('quantity')
                                ->label('Кількість')
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->required()
                                ->suffix('шт'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $quantity = (int) $data['quantity'];
                            $records->each(fn (ProductVariant $variant) => $data['mode'] === 'add'
                                ? $variant->increment('stock', $quantity)
                                : $variant->update(['stock' => $quantity]));
                        })
                        ->successNotificationTitle('Залишки оновлено')
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Зняти з продажу')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
