<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Дії на сторінці «Оплата не пройшла» (StatePaymentFailed / DeskPaymentFailed):
 * спробувати ще раз або перейти на оплату при отриманні, поки замовлення тримаємо
 * config('shop.payment_hold_hours') год від створення.
 */
class PaymentRetry extends Component
{
    #[Locked]
    public int $orderId;

    public function mount(Order $order): void
    {
        $this->orderId = $order->id;
    }

    /** До котрої тримаємо товар за замовленням */
    public static function holdUntil(Order $order): Carbon
    {
        return $order->created_at->copy()->addHours((int) config('shop.payment_hold_hours'));
    }

    /** Онлайн-оплата ще не пройшла й час тримання не минув */
    public static function canSwitch(Order $order): bool
    {
        return $order->payment_method === PaymentMethod::Online
            && in_array($order->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true)
            && now()->lt(self::holdUntil($order));
    }

    /** «ще 2 години» · «ще 1 годину 15 хвилин» · «ще 5 хвилин» */
    public static function remainingLabel(Order $order): string
    {
        $minutes = max(1, (int) ceil(now()->diffInSeconds(self::holdUntil($order), false) / 60));
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return collect([
            $hours ? plural_uk($hours, 'годину', 'години', 'годин') : null,
            $rest ? plural_uk($rest, 'хвилину', 'хвилини', 'хвилин') : null,
        ])->filter()->implode(' ');
    }

    /** «Оплатити при отриманні»: міняємо спосіб оплати на післяплату й ведемо на «Замовлення прийнято» */
    public function payOnDelivery()
    {
        $order = DB::transaction(function (): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($this->orderId);

            if (self::canSwitch($order)) {
                $order->update([
                    'payment_method' => PaymentMethod::CashOnDelivery,
                    'payment_status' => PaymentStatus::Pending,
                ]);
            }

            return $order;
        });

        if ($order->payment_method !== PaymentMethod::CashOnDelivery) {
            // Час вийшов або замовлення вже оплачене — показуємо актуальний стан
            return $this->redirectRoute('order.payment-failed', $order);
        }

        return $this->redirectRoute('order.success', $order);
    }

    public function render(): View
    {
        $order = Order::query()->findOrFail($this->orderId);

        return view('livewire.payment-retry', [
            'order' => $order,
            'active' => self::canSwitch($order),
            'holdUntil' => self::holdUntil($order)->tz(config('app.timezone')),
        ]);
    }
}
