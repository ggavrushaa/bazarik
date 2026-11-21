<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** «Дякуємо, замовлення прийнято» (StateSuccess / DeskSuccess). Доступ — за public_token у посиланні, без входу. */
    public function success(Order $order): View
    {
        $order->load('items');

        return view('pages.order-success', ['order' => $order]);
    }

    /**
     * «Оплата не пройшла» (StatePaymentFailed / DeskPaymentFailed): лише для онлайн-оплати, яку ще не сплачено.
     * Решта (післяплата, вже оплачене) — на «Замовлення прийнято». Перехід на післяплату — App\Livewire\PaymentRetry.
     */
    public function paymentFailed(Order $order): View|RedirectResponse
    {
        if ($order->payment_method !== PaymentMethod::Online || ! in_array($order->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true)) {
            return redirect()->route('order.success', $order);
        }

        return view('pages.payment-failed', ['order' => $order]);
    }
}
