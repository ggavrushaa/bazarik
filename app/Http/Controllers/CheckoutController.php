<?php

namespace App\Http\Controllers;

use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Оформлення без акаунта: MobileCheckout / DeskCheckout (форма — Livewire CheckoutForm) */
class CheckoutController extends Controller
{
    public function __invoke(Cart $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart');
        }

        return view('pages.checkout');
    }
}
