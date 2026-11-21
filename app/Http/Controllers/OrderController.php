<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** «Дякуємо, замовлення прийнято» (StateSuccess / DeskSuccess). Доступ — за public_token у посиланні, без входу. */
    public function success(Order $order): View
    {
        $order->load('items');

        return view('pages.order-success', ['order' => $order]);
    }

    public function paymentFailed(Order $order)
    {
        abort(404); // TODO: етап 2
    }
}
