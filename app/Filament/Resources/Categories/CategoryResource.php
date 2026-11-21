<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Filament\Support\ImageField;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
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
use UnitEnum;

class CategoryResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'категорія';

    protected static ?string $pluralModelLabel = 'категорії';

    protected static ?string $navigationLabel = 'Категорії';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Назва')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                    if (blank($get('slug')) || $get('slug') === ProductResource::slug($old)) {
                                        $set('slug', ProductResource::slug($state));
                                    }
                                }),
                            TextInput::make('slug')
                                ->label('Адреса сторінки')
                                ->prefix('/catalog/')
                                ->required()
                                ->alphaDash()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            Select::make('parent_id')
                                ->label('Батьківська категорія')
                                ->helperText('Порожньо — розділ верхнього рівня. Підкатегорії показуються чипами в каталозі.')
                                ->relationship(
                                    'parent',
                                    'name',
                                    fn (Builder $query, ?Category $record) => $query
                                        ->whereNull('parent_id')
                                        ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                                        ->orderBy('sort'),
                                )
                                ->placeholder('— верхній рівень —')
                                ->preload(),
                            TextInput::make('sort')
                                ->label('Порядок')
                                ->numeric()
                                ->integer()
                                ->default(0)
                                ->required(),
                            Textarea::make('description')
                                ->label('Опис')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                    Section::make('SEO')
                        ->collapsible()
                        ->schema(ProductResource::seoFields()),
                ])->columnSpan(['lg' => 2]),
                Section::make()
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Показувати на вітрині')
                            ->default(true),
                        ...ImageField::make('image', 'categories'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent')->withCount('products'))
            // Деревом: розділ, під ним його підкатегорії
            ->defaultSort(fn (Builder $query) => $query
                ->orderByRaw('COALESCE((SELECT p.sort FROM categories p WHERE p.id = categories.parent_id), categories.sort)')
                ->orderByRaw('COALESCE(categories.parent_id, categories.id)')
                ->orderByRaw('categories.parent_id IS NOT NULL')
                ->orderBy('categories.sort'))
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->state(fn (Category $record): ?string => ImageField::url($record->image))
                    ->square()
                    ->imageSize(40),
                TextColumn::make('name')
                    ->label('Назва')
                    ->formatStateUsing(fn (string $state, Category $record): string => $record->parent_id ? '↳ '.$state : $state)
                    ->weight(fn (Category $record): ?string => $record->parent_id ? null : 'bold')
                    ->description(fn (Category $record): ?string => $record->parent?->name)
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Адреса')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('products_count')
                    ->label('Товарів')
                    ->alignEnd(),
                TextColumn::make('sort')
                    ->label('Порядок')
                    ->alignEnd()
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('На вітрині'),
            ])
            ->filters([
                SelectFilter::make('parent')
                    ->label('Розділ')
                    ->relationship('parent', 'name', fn (Builder $query) => $query->whereNull('parent_id')->orderBy('sort')),
                TernaryFilter::make('root')
                    ->label('Рівень')
                    ->trueLabel('Лише розділи')
                    ->falseLabel('Лише підкатегорії')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('parent_id'),
                        false: fn (Builder $query) => $query->whereNotNull('parent_id'),
                    ),
                TernaryFilter::make('is_active')
                    ->label('На вітрині'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
