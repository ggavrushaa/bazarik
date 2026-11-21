<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Support\OrderWorkflow;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/** Кнопки обробки замовлення — і в шапці сторінки замовлення, і в рядках таблиці */
class OrderActions
{
    /** @return array<Action> */
    public static function all(): array
    {
        return [
            self::confirm(),
            self::packing(),
            self::ship(),
            self::arrived(),
            self::complete(),
            self::returned(),
            self::cancel(),
        ];
    }

    public static function confirm(): Action
    {
        return self::transition('confirm', 'Підтвердити', OrderStatus::Confirmed, [OrderStatus::New])
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->successNotificationTitle('Замовлення підтверджене');
    }

    public static function packing(): Action
    {
        return self::transition('packing', 'Збираємо', OrderStatus::Packing, [OrderStatus::Confirmed])
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->successNotificationTitle('Замовлення збирають на складі');
    }

    public static function ship(): Action
    {
        return Action::make('ship')
            ->label('Передано в НП')
            ->icon(Heroicon::OutlinedTruck)
            ->color('primary')
            ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Packing], true))
            ->modalHeading('Передано в Нову Пошту')
            ->modalDescription('Вкажіть номер накладної — покупець побачить його на сторінці відстеження.')
            ->modalSubmitActionLabel('Зберегти')
            ->fillForm(fn (Order $record): array => ['ttn' => $record->ttn])
            ->schema([
                TextInput::make('ttn')
                    ->label('Номер ТТН')
                    ->required()
                    ->regex('/^\d{14}$/')
                    ->validationMessages(['regex' => 'ТТН Нової Пошти — 14 цифр.'])
                    ->placeholder('20450000000000'),
            ])
            ->action(function (Order $record, array $data, Action $action): void {
                OrderWorkflow::transition($record, OrderStatus::Shipped, attributes: ['ttn' => $data['ttn']]);
                $action->success();
            })
            ->successNotificationTitle('Замовлення передано в НП')
            ->after(self::refreshForm(...));
    }

    public static function arrived(): Action
    {
        return self::transition('arrived', 'У відділенні', OrderStatus::Arrived, [OrderStatus::Shipped])
            ->icon(Heroicon::OutlinedBuildingStorefront)
            ->color('gray');
    }

    public static function complete(): Action
    {
        return self::transition('complete', 'Отримане', OrderStatus::Completed, [OrderStatus::Shipped, OrderStatus::Arrived])
            ->icon(Heroicon::OutlinedHandThumbUp)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Покупець отримав замовлення?')
            ->modalDescription('Післяплату позначимо оплаченою.');
    }

    public static function returned(): Action
    {
        return self::transition('return', 'Повернення', OrderStatus::Returned, [OrderStatus::Shipped, OrderStatus::Arrived, OrderStatus::Completed])
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Оформити повернення?')
            ->modalDescription('Товари з замовлення повернуться на залишок складу.')
            ->after(self::warnAboutRefund(...));
    }

    public static function cancel(): Action
    {
        return self::transition('cancel', 'Скасувати', OrderStatus::Cancelled, [OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Packing])
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Скасувати замовлення?')
            ->modalDescription('Товари повернуться на залишок складу. Скасування не можна відмінити.')
            ->modalSubmitActionLabel('Так, скасувати')
            ->successNotificationTitle('Замовлення скасоване')
            ->after(self::warnAboutRefund(...));
    }

    /** @param  array<OrderStatus>  $from */
    private static function transition(string $name, string $label, OrderStatus $to, array $from): Action
    {
        return Action::make($name)
            ->label($label)
            ->visible(fn (Order $record): bool => in_array($record->status, $from, true))
            ->action(function (Order $record, Action $action) use ($to): void {
                OrderWorkflow::transition($record, $to);
                $action->success();
            })
            ->successNotificationTitle('Статус змінено: '.$to->getLabel())
            ->after(self::refreshForm(...));
    }

    private static function refreshForm(mixed $livewire): void
    {
        if ($livewire instanceof EditRecord) {
            $livewire->refreshFormData(['status', 'ttn', 'payment_status', 'shipped_at', 'delivered_at']);
        }
    }

    private static function warnAboutRefund(Order $record, mixed $livewire): void
    {
        self::refreshForm($livewire);

        if ($record->payment_status === PaymentStatus::Paid) {
            Notification::make()
                ->warning()
                ->title('Замовлення було оплачене')
                ->body('Поверніть гроші покупцю через платіжний сервіс і змініть статус оплати на «Повернено».')
                ->persistent()
                ->send();
        }
    }
}
