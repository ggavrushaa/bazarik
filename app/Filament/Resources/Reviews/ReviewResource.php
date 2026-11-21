<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/** Модерація відгуків: на вітрині лише опубліковані */
class ReviewResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = 'Покупці';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'відгук';

    protected static ?string $pluralModelLabel = 'відгуки';

    protected static ?string $navigationLabel = 'Відгуки';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationBadge(): ?string
    {
        $count = Review::query()->where('is_published', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Чекають на модерацію';
    }

    public static function canCreate(): bool
    {
        return false; // відгуки пишуть покупці на вітрині
    }

    public static function stars(int $rating): string
    {
        return str_repeat('★', $rating).str_repeat('☆', max(0, 5 - $rating));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('author_name')
                            ->label("Ім'я автора")
                            ->required()
                            ->maxLength(255),
                        Select::make('rating')
                            ->label('Оцінка')
                            ->options(collect(range(5, 1))->mapWithKeys(fn (int $r) => [$r => self::stars($r)]))
                            ->required(),
                        TextInput::make('variant_label')
                            ->label('Варіант')
                            ->placeholder('Купив розмір M')
                            ->maxLength(255),
                        Textarea::make('text')
                            ->label('Текст')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
                Section::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Опубліковано'),
                        TextEntry::make('product.name')
                            ->label('Товар')
                            ->url(fn (Review $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id])),
                        TextEntry::make('order.number')
                            ->label('Перевірена покупка')
                            ->placeholder('Ні — замовлення не прив’язане'),
                        TextEntry::make('created_at')
                            ->label('Написано')
                            ->dateTime('d.m.Y H:i'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Товар')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('author_name')
                    ->label('Автор')
                    ->description(fn (Review $record): ?string => $record->variant_label)
                    ->searchable(),
                TextColumn::make('rating')
                    ->label('Оцінка')
                    ->formatStateUsing(fn (int $state): string => self::stars($state))
                    ->color(fn (int $state): string => $state <= 2 ? 'danger' : 'warning')
                    ->sortable(),
                TextColumn::make('text')
                    ->label('Текст')
                    ->limit(120)
                    ->wrap()
                    ->placeholder('Без тексту')
                    ->searchable(),
                IconColumn::make('order_id')
                    ->label('Перевірена')
                    ->state(fn (Review $record): bool => $record->isVerified())
                    ->boolean()
                    ->alignCenter(),
                ToggleColumn::make('is_published')
                    ->label('Опубліковано'),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Публікація')
                    ->trueLabel('Опубліковані')
                    ->falseLabel('Чекають на модерацію'),
                SelectFilter::make('rating')
                    ->label('Оцінка')
                    ->options(collect(range(5, 1))->mapWithKeys(fn (int $r) => [$r => self::stars($r)]))
                    ->multiple(),
                SelectFilter::make('product')
                    ->label('Товар')
                    ->relationship('product', 'name')
                    ->searchable(),
                TernaryFilter::make('verified')
                    ->label('Перевірена покупка')
                    ->nullable()
                    ->attribute('order_id'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Опублікувати')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->successNotificationTitle('Відгуки опубліковано')
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label('Зняти з публікації')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}
