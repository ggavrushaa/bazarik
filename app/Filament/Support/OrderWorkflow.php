<?php

namespace App\Filament\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Переходи між статусами замовлення в адмінці та їхні побічні ефекти:
 * дати відправки/отримання, оплата післяплати, повернення залишку на склад.
 */
class OrderWorkflow
{
    /** Статуси, у яких товар уже повернули на склад (або він туди повернеться) */
    public const RESTOCKED = [OrderStatus::Cancelled, OrderStatus::Returned];

    public static function isFinal(Order $order): bool
    {
        return in_array($order->status, [...self::RESTOCKED, OrderStatus::Completed], true);
    }

    /**
     * Застосувати новий статус і зберегти. $from — статус до зміни (коли модель уже змінили формою).
     */
    public static function transition(Order $order, OrderStatus $to, ?OrderStatus $from = null, array $attributes = []): void
    {
        $from ??= $order->status;

        DB::transaction(function () use ($order, $to, $from, $attributes): void {
            $order->fill($attributes);
            $order->status = $to;

            if ($to === OrderStatus::Shipped && ! $order->shipped_at) {
                $order->shipped_at = now();
            }

            if ($to === OrderStatus::Completed) {
                $order->delivered_at ??= now();

                // Накладений платіж: гроші отримали разом із посилкою
                if ($order->payment_method === PaymentMethod::CashOnDelivery && $order->payment_status !== PaymentStatus::Paid) {
                    $order->payment_status = PaymentStatus::Paid;
                    $order->paid_at ??= now();
                }
            }

            $order->save();

            if (in_array($to, self::RESTOCKED, true) && ! in_array($from, self::RESTOCKED, true)) {
                self::returnStock($order);
            }
        });
    }

    /** Повертає кількість з позицій замовлення на залишок варіантів */
    public static function returnStock(Order $order): void
    {
        $order->loadMissing('items.variant');

        foreach ($order->items as $item) {
            $item->variant?->increment('stock', $item->quantity);
        }
    }
}
