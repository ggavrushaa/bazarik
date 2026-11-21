<?php

namespace App\Filament\Resources\Products;

use App\Enums\ProductBadge;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'товари';

    protected static ?string $navigationLabel = 'Товари';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'variants.sku'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('product')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Основне')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->columns(2)
                            ->schema(self::mainFields()),
                        Tab::make('Опис')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Textarea::make('short_description')
                                    ->label('Коротко')
                                    ->helperText('1–2 речення під назвою: що це й чим корисне.')
                                    ->maxLength(500)
                                    ->rows(2),
                                Textarea::make('description')
                                    ->label('Опис')
                                    ->helperText('Простий текст без розмітки — так він і показується на сторінці товару.')
                                    ->rows(8),
                                Textarea::make('care')
                                    ->label('Склад і догляд')
                                    ->rows(3),
                            ]),
                        Tab::make('Одяг')
                            ->icon(Heroicon::OutlinedSwatch)
                            ->schema(self::apparelFields()),
                        Tab::make('Простими словами')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->schema([
                                Repeater::make('highlights')
                                    ->hiddenLabel()
                                    ->helperText('Плитки «~4 зарядки — смартфона на одному заряді». Найкраще 4 штуки.')
                                    ->table([
                                        TableColumn::make('Значення')->width('35%'),
                                        TableColumn::make('Пояснення'),
                                    ])
                                    ->schema([
                                        TextInput::make('value')->required()->maxLength(40)->placeholder('~4 зарядки'),
                                        TextInput::make('text')->required()->maxLength(120)->placeholder('смартфона на одному заряді'),
                                    ])
                                    ->reorderable()
                                    ->defaultItems(0)
                                    ->maxItems(8)
                                    ->addActionLabel('Додати плитку'),
                            ]),
                        Tab::make('Характеристики')
                            ->icon(Heroicon::OutlinedListBullet)
                            ->schema([
                                Repeater::make('specs')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Назва')->width('40%'),
                                        TableColumn::make('Значення'),
                                    ])
                                    ->schema([
                                        TextInput::make('label')->required()->placeholder('Ємність'),
                                        TextInput::make('value')->required()->placeholder('20 000 мАг'),
                                    ])
                                    ->reorderable()
                                    ->defaultItems(0)
                                    ->addActionLabel('Додати характеристику'),
                            ]),
                        Tab::make('Набір і відео')
                            ->icon(Heroicon::OutlinedGift)
                            ->columns(2)
                            ->schema([
                                Section::make('Разом дешевше')
                                    ->description('Товар, який пропонуємо докупити зі знижкою на набір. Не працює для одягу.')
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        Select::make('bundle_product_id')
                                            ->label('Товар у наборі')
                                            ->relationship(
                                                'bundleProduct',
                                                'name',
                                                fn (Builder $query, ?Product $record) => $query
                                                    ->where('is_apparel', false)
                                                    ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey())),
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                        TextInput::make('bundle_discount')
                                            ->label('Знижка на набір')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->maxValue(90)
                                            ->suffix('%')
                                            ->required(fn (Get $get): bool => filled($get('bundle_product_id'))),
                                    ]),
                                Section::make('Відео')
                                    ->description('Коротке відео з Reels чи TikTok на сторінці товару.')
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        TextInput::make('video_url')
                                            ->label('Посилання на відео')
                                            ->url()
                                            ->maxLength(255),
                                        TextInput::make('video_seconds')
                                            ->label('Тривалість')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->suffix('с'),
                                    ]),
                            ]),
                        Tab::make('SEO')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema(self::seoFields()),
                    ]),
            ]);
    }

    /** @return array<Component> */
    private static function mainFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Назва')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                    // Оновлюємо адресу, поки її не правили вручну
                    if (blank($get('slug')) || $get('slug') === self::slug($old)) {
                        $set('slug', self::slug($state));
                    }
                })
                ->columnSpanFull(),
            TextInput::make('slug')
                ->label('Адреса сторінки')
                ->prefix('/product/')
                ->helperText('Латиницею, створюється з назви.')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->unique(ignoreRecord: true),
            Select::make('category_id')
                ->label('Категорія')
                ->relationship('category', 'name', fn (Builder $query) => $query->with('parent')->orderBy('parent_id')->orderBy('sort'))
                ->getOptionLabelFromRecordUsing(fn (Category $record): string => self::categoryLabel($record))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('price')
                ->label('Ціна')
                ->required()
                ->numeric()
                ->minValue(0)
                ->suffix('₴'),
            TextInput::make('old_price')
                ->label('Стара ціна')
                ->helperText('Закреслена ціна для «−20%». Порожньо — без знижки.')
                ->numeric()
                ->gt('price')
                ->validationMessages(['gt' => 'Стара ціна має бути більшою за ціну.'])
                ->suffix('₴'),
            Select::make('badge')
                ->label('Бейдж')
                ->options(ProductBadge::class)
                ->placeholder('Без бейджа'),
            TextInput::make('sort')
                ->label('Порядок')
                ->helperText('Менше — вище в каталозі.')
                ->numeric()
                ->integer()
                ->default(0)
                ->required(),
            TextInput::make('initial_stock')
                ->label('Залишок на складі')
                ->helperText('Для товару без кольорів і розмірів створимо один варіант з цим залишком.')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0)
                ->dehydrated(false)
                ->visibleOn('create')
                ->hidden(fn (Get $get): bool => (bool) $get('is_apparel')),
            Toggle::make('is_active')
                ->label('Показувати на вітрині')
                ->default(true)
                ->columnSpanFull(),
        ];
    }

    /** @return array<Component> */
    private static function apparelFields(): array
    {
        $visible = fn (Get $get): bool => (bool) $get('is_apparel');

        return [
            Toggle::make('is_apparel')
                ->label('Це одяг')
                ->helperText('Колір і розмір обирають на сторінці товару; варіанти додаються в блоці «Варіанти й залишки» нижче.')
                ->live(),
            Grid::make(2)
                ->visible($visible)
                ->schema([
                    TextInput::make('fit_hint')
                        ->label('Посадка')
                        ->placeholder('Сідає розмір у розмір')
                        ->maxLength(255),
                    TextInput::make('model_note')
                        ->label('Модель на фото')
                        ->placeholder('Зріст 172 см, розмір M')
                        ->maxLength(255),
                ]),
            Section::make('Таблиця розмірів')
                ->visible($visible)
                ->schema([
                    TextInput::make('size_chart.title')
                        ->label('Заголовок')
                        ->placeholder('Заміри виробу, см'),
                    TagsInput::make('size_chart.columns')
                        ->label('Стовпці')
                        ->placeholder('Розмір, Груди, Довжина…')
                        ->helperText('Натисніть Enter після кожного стовпця. Можна перетягувати.')
                        ->reorderable(),
                    Textarea::make('size_chart.rows')
                        ->label('Рядки')
                        ->helperText('Один розмір на рядок, значення через «|» у порядку стовпців: S | 116 | 68 | 58 | 158–166')
                        ->rows(6)
                        ->afterStateHydrated(function (Textarea $component, $state): void {
                            if (is_array($state)) {
                                $component->state(collect($state)->map(fn ($row) => implode(' | ', (array) $row))->implode("\n"));
                            }
                        })
                        ->dehydrateStateUsing(fn (?string $state): array => self::parseSizeChartRows($state)),
                ]),
        ];
    }

    /** @return array<Component> */
    public static function seoFields(): array
    {
        return [
            TextInput::make('seo_title')
                ->label('Заголовок для пошуковиків')
                ->helperText('Порожньо — візьмемо назву.')
                ->maxLength(255),
            Textarea::make('seo_description')
                ->label('Опис для пошуковиків')
                ->helperText('До 160 символів. Порожньо — візьмемо короткий опис.')
                ->maxLength(500)
                ->rows(3),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['mainImage', 'category'])
                ->withSum(['variants as active_stock' => fn (Builder $q) => $q->where('is_active', true)], 'stock')
                ->withCount('variants'))
            ->defaultSort('sort')
            ->paginated([25, 50, 100])
            ->columns([
                ImageColumn::make('mainImage.url')
                    ->label('')
                    ->square()
                    ->imageSize(48),
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Product $record): ?string => $record->badge?->getLabel()),
                TextColumn::make('category.name')
                    ->label('Категорія')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Ціна')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->description(fn (Product $record): ?string => $record->old_price ? 'було '.money($record->old_price) : null)
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('active_stock')
                    ->label('Залишок')
                    ->formatStateUsing(fn ($state): string => ((int) $state).' шт')
                    ->default(0)
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (int) $state === 0 => 'danger',
                        (int) $state <= 3 => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (Product $record): ?string => $record->variants_count > 1 ? $record->variants_count.' варіантів' : null)
                    ->alignEnd()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('На вітрині'),
                TextColumn::make('updated_at')
                    ->label('Змінено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Категорія')
                    ->relationship('category', 'name', fn (Builder $query) => $query->with('parent')->orderBy('parent_id')->orderBy('sort'))
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => self::categoryLabel($record))
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_apparel')
                    ->label('Одяг')
                    ->trueLabel('Лише одяг')
                    ->falseLabel('Без одягу'),
                TernaryFilter::make('in_stock')
                    ->label('Наявність')
                    ->trueLabel('Є на складі')
                    ->falseLabel('Закінчився')
                    ->queries(
                        true: fn (Builder $query) => $query->inStock(),
                        false: fn (Builder $query) => $query->whereDoesntHave('variants', fn (Builder $q) => $q->where('is_active', true)->where('stock', '>', 0)),
                    ),
                TernaryFilter::make('is_active')
                    ->label('На вітрині'),
            ])
            ->recordActions([
                Action::make('storefront')
                    ->label('')
                    ->tooltip('Відкрити на вітрині')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Product $record): string => route('product.show', $record), shouldOpenInNewTab: true),
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Показати на вітрині')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (EloquentCollection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Сховати з вітрини')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (EloquentCollection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function slug(?string $value): string
    {
        return Str::slug((string) $value, '-', 'uk');
    }

    public static function categoryLabel(Category $category): string
    {
        return $category->parent ? $category->parent->name.' → '.$category->name : $category->name;
    }

    /** «S | 116 | 68» по рядках → [['S', '116', '68'], …] */
    public static function parseSizeChartRows(?string $text): array
    {
        return collect(preg_split('/\R/u', (string) $text))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(fn (string $line) => array_map('trim', explode('|', $line)))
            ->values()
            ->all();
    }

    /** Прибираємо порожні JSON-поля, щоб вітрина не показувала порожні блоки */
    public static function normalizeFormData(array $data): array
    {
        foreach (['highlights', 'specs'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        if (array_key_exists('size_chart', $data)) {
            $chart = $data['size_chart'] ?? [];
            $data['size_chart'] = blank($chart['rows'] ?? null) && blank($chart['columns'] ?? null) ? null : [
                'title' => $chart['title'] ?? null,
                'columns' => array_values($chart['columns'] ?? []),
                'rows' => array_values($chart['rows'] ?? []),
            ];
        }

        if (blank($data['bundle_product_id'] ?? null)) {
            $data['bundle_discount'] = null;
        }

        return $data;
    }
}
