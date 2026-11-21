<?php

namespace App\Livewire;

use App\Models\Order;
use App\Support\Cart;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * «Повторити» / «Повторити замовлення»: кладемо в кошик те з замовлення, що ще є в наявності,
 * і ведемо в кошик. Кнопка у списку замовлень і на сторінці замовлення.
 */
class AccountReorder extends Component
{
    #[Locked]
    public int $orderId;

    #[Locked]
    public string $label = 'Повторити';

    #[Locked]
    public string $btn = 'bz-btn bz-btn--secondary';

    public ?string $error = null;

    public function mount(Order $order, string $label = 'Повторити', string $btn = 'bz-btn bz-btn--secondary'): void
    {
        $this->orderId = $order->id;
        $this->label = $label;
        $this->btn = $btn;
    }

    public function reorder(Cart $cart): void
    {
        $order = Order::query()
            ->whereKey($this->orderId)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        if (self::addToCart($order, $cart) === 0) {
            $this->error = 'Цих товарів зараз немає в наявності';

            return;
        }

        $this->redirectRoute('cart');
    }

    /** @return int скільки позицій додали в кошик */
    public static function addToCart(Order $order, Cart $cart): int
    {
        $added = 0;
        $order->loadMissing('items.variant.product');

        foreach ($order->items as $item) {
            $variant = $item->variant;
            if (! $variant?->product?->is_active || ! $variant->isAvailable()) {
                continue;
            }
            if ($cart->add($variant, $item->quantity) > 0) {
                $added++;
            }
        }

        return $added;
    }

    public function render(): View
    {
        return view('livewire.account-reorder');
    }
}
