<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** «Де моє замовлення»: форма за номером і телефоном (StateTracking) і сторінка за посиланням з SMS (StateTracking / DeskTracking) */
class TrackingController extends Controller
{
    public function form(): View
    {
        return view('pages.tracking');
    }

    public function show(Order $order): View
    {
        $order->load('items.product.mainImage');

        return view('pages.tracking-show', ['order' => $order]);
    }

    /** Коли посилка буде у відділенні: від відправки (або оформлення), але не раніше, ніж якщо відправити зараз */
    public static function expectedAt(Order $order): Carbon
    {
        $tz = config('app.timezone');
        $from = ($order->shipped_at ?? $order->created_at)->copy()->tz($tz);
        $date = delivery_date($from);

        // Розрахункова дата вже минула, а посилки ще немає — рахуємо від сьогодні
        return $date->copy()->startOfDay()->lt(now($tz)->startOfDay()) ? delivery_date() : $date;
    }

    /**
     * Кроки для таймлайну з поточного статусу.
     * $transit — додати крок «У дорозі» (десктоп, DeskTracking).
     *
     * @return list<array{label: string, note: string, done: bool}>
     */
    public static function timeline(Order $order, bool $transit = false, bool $place = false): array
    {
        $tz = config('app.timezone');
        $at = fn (?Carbon $d) => $d ? $d->copy()->tz($tz)->translatedFormat('j F, H:i') : '';
        $ttn = $order->ttn ? 'ТТН '.$order->ttn : '';
        $expected = self::expectedAt($order);
        $point = AccountController::place($order);

        $accepted = ['label' => 'Прийнято', 'note' => $at($order->created_at), 'done' => true];

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
            return [$accepted, ['label' => $order->status->getLabel(), 'note' => $at($order->updated_at), 'done' => true]];
        }

        $rank = match ($order->status) {
            OrderStatus::Shipped => 2,
            OrderStatus::Arrived => 3,
            OrderStatus::Completed => 4,
            default => 1,
        };

        $steps = [$accepted];
        $steps[] = [
            'label' => 'Відправлено',
            'note' => collect([$ttn, $order->shipped_at ? $at($order->shipped_at) : null])->filter()->implode(' · '),
            'done' => $rank >= 2,
        ];
        if ($transit) {
            $steps[] = ['label' => 'У дорозі', 'note' => $rank === 2 ? 'прибуде '.delivery_label($expected, lower: true) : '', 'done' => $rank >= 2];
        }
        $steps[] = [
            'label' => 'У відділенні',
            'note' => collect([
                $place ? $point : null,
                $rank < 3 ? 'очікується '.$expected->translatedFormat('j F') : null,
            ])->filter()->implode(' · '),
            'done' => $rank >= 3,
        ];
        $steps[] = ['label' => 'Отримано', 'note' => $rank >= 4 ? $at($order->delivered_at) : '', 'done' => $rank >= 4];

        return $steps;
    }
}
