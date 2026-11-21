<?php

namespace App\Filament\Resources\Collections;

use App\Filament\Resources\Collections\Pages\CreateCollection;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\Collections\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Filament\Support\ImageField;
use App\Models\Collection;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Підбірки: «на блекаут», «подарунок до 500 ₴» */
class CollectionResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'підбірка';

    protected static ?string $pluralModelLabel = 'підбірки';

    protected static ?string $navigationLabel = 'Підбірки';

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
                                ->required()
                                ->alphaDash()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            Textarea::make('description')
                                ->label('Опис')
                                ->rows(3)
                                ->columnSpanFull(),
                            TextInput::make('sort')
                                ->label('Порядок')
                                ->numeric()
                                ->integer()
                                ->default(0)
                                ->required(),
                        ]),
                    Section::make('SEO')
                        ->collapsible()
                        ->schema(ProductResource::seoFields()),
                ])->columnSpan(['lg' => 2]),
                Section::make()
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),
                        Toggle::make('show_on_home')
                            ->label('Показувати на головній'),
                        ...ImageField::make('image', 'collections', 'Обкладинка'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->state(fn (Collection $record): ?string => ImageField::url($record->image))
                    ->square()
                    ->imageSize(40),
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('products_count')
                    ->label('Товарів')
                    ->alignEnd(),
                ToggleColumn::make('show_on_home')
                    ->label('На головній'),
                ToggleColumn::make('is_active')
                    ->label('Активна'),
            ])
            ->filters([
                TernaryFilter::make('show_on_home')->label('На головній'),
                TernaryFilter::make('is_active')->label('Активна'),
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

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }
}
