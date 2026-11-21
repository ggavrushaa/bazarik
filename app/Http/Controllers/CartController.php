<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Кошик: MobileCart / DeskCart, порожній — StateCartEmpty / DeskCartEmpty (усе в Livewire CartPage) */
class CartController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.cart');
    }
}
