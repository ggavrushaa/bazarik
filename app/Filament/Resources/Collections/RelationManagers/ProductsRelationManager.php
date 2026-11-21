<?php

namespace App\Filament\Resources\Collections\RelationManagers;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Товари в підбірці';

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'товари';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description('Порядок змінюється перетягуванням.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('mainImage'))
            ->reorderable('sort')
            ->columns([
                ImageColumn::make('mainImage.url')
                    ->label('')
                    ->square()
                    ->imageSize(40),
                TextColumn::make('name')
                    ->label('Товар')
                    ->searchable()
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('price')
                    ->label('Ціна')
                    ->formatStateUsing(fn ($state): string => money($state))
                    ->alignEnd(),
                TextColumn::make('pivot.sort')
                    ->label('Порядок')
                    ->alignEnd(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Додати товар')
                    ->preloadRecordSelect()
                    ->multiple()
                    ->recordSelectSearchColumns(['name', 'slug'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()->label('Товари'),
                        TextInput::make('sort')
                            ->label('Порядок у підбірці')
                            ->numeric()
                            ->integer()
                            ->default(fn (): int => (int) $this->getOwnerRecord()->products()->max('collection_product.sort') + 1)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                DetachAction::make()->label('Прибрати'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Прибрати з підбірки'),
                ]),
            ]);
    }
}
