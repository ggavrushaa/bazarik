<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductImage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** Фото товару: перше за порядком — головне (картка в каталозі) */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Фото';

    protected static ?string $modelLabel = 'фото';

    protected static ?string $pluralModelLabel = 'фото';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedPhoto;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // Демо-фото лежать у public/ (шлях з «/») — їх не перезавантажуємо, лише показуємо
                ImageEntry::make('url')
                    ->label('Фото')
                    ->imageHeight(160)
                    ->visible(fn (?ProductImage $record): bool => $record && self::isExternal($record->path)),
                FileUpload::make('path')
                    ->label('Фото')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->visibility('public')
                    ->imageEditor()
                    ->maxSize(5120)
                    ->required()
                    ->hidden(fn (?ProductImage $record): bool => $record && self::isExternal($record->path)),
                TextInput::make('alt')
                    ->label('Опис фото (alt)')
                    ->helperText('Що на фото — для пошуковиків і екранних читачів.')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->paginated(false)
            ->columns([
                ImageColumn::make('url')
                    ->label('')
                    ->imageSize(72),
                TextColumn::make('alt')
                    ->label('Опис')
                    ->placeholder('—')
                    ->description(fn (ProductImage $record, $rowLoop): ?string => $rowLoop->first ? 'Головне фото' : null),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Завантажити фото')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->modalHeading('Завантажити фото')
                    ->modalSubmitActionLabel('Завантажити')
                    ->schema([
                        FileUpload::make('paths')
                            ->label('Фото')
                            ->helperText('Можна кілька одразу. Порядок потім змінюється перетягуванням.')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->required(),
                        TextInput::make('alt')
                            ->label('Опис фото (alt)')
                            ->default(fn (): string => $this->getOwnerRecord()->name),
                    ])
                    ->action(function (array $data): void {
                        $product = $this->getOwnerRecord();
                        $sort = (int) $product->images()->max('sort');
                        foreach (array_values((array) $data['paths']) as $path) {
                            $product->images()->create(['path' => $path, 'alt' => $data['alt'] ?? null, 'sort' => ++$sort]);
                        }
                    })
                    ->successNotificationTitle('Фото додано'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function isExternal(?string $path): bool
    {
        return Str::startsWith((string) $path, ['/', 'http://', 'https://']);
    }
}
