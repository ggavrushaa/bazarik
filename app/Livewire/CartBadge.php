<?php

namespace App\Livewire;

use App\Support\Cart;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Іконка кошика в шапці: кількість (телефон) або кількість + сума (десктоп) */
class CartBadge extends Component
{
    public string $variant = 'mob';

    #[On('cart-updated')]
    public function refresh(): void {}

    public function render(Cart $cart): View
    {
        return view('livewire.cart-badge', [
            'count' => $cart->count(),
            'subtotal' => $cart->total(),
        ]);
    }
}
