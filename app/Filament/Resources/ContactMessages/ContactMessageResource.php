<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Support\CapitalizedPluralLabel;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Повідомлення з форми «Напишіть нам» — лише перегляд і позначка «опрацьовано» */
class ContactMessageResource extends Resource
{
    use CapitalizedPluralLabel;

    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Покупці';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'повідомлення';

    protected static ?string $pluralModelLabel = 'повідомлення';

    protected static ?string $navigationLabel = 'Повідомлення';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()->whereNull('handled_at')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label("Ім'я"),
            TextEntry::make('phone')->label('Телефон')->copyable()->url(fn (ContactMessage $record): string => 'tel:'.$record->phone),
            TextEntry::make('customer.phone')
                ->label('Акаунт')
                ->placeholder('Без акаунта')
                ->url(fn (ContactMessage $record): ?string => $record->customer_id ? CustomerResource::getUrl('view', ['record' => $record->customer_id]) : null),
            TextEntry::make('created_at')->label('Надіслано')->dateTime('d.m.Y H:i'),
            TextEntry::make('message')->label('Повідомлення')->columnSpanFull(),
            TextEntry::make('handled_at')->label('Опрацьовано')->dateTime('d.m.Y H:i')->placeholder('Ще ні'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('name')->label("Ім'я")->description(fn (ContactMessage $record): string => $record->phone)->searchable(['name', 'phone']),
                TextColumn::make('message')->label('Повідомлення')->limit(120)->wrap()->searchable(),
                TextColumn::make('handled_at')->label('Опрацьовано')->dateTime('d.m.Y H:i')->placeholder('Ще ні')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('pending')
                    ->label('Статус')
                    ->trueLabel('Нові')
                    ->falseLabel('Опрацьовані')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('handled_at'),
                        false: fn (Builder $query) => $query->whereNotNull('handled_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('handle')
                    ->label('Опрацьовано')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (ContactMessage $record): bool => $record->handled_at === null)
                    ->action(fn (ContactMessage $record) => $record->update(['handled_at' => now()])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('handle')
                        ->label('Позначити опрацьованими')
                        ->icon(Heroicon::OutlinedCheck)
                        ->action(fn (Collection $records) => ContactMessage::query()->whereKey($records->modelKeys())->whereNull('handled_at')->update(['handled_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
        ];
    }
}
