<?php

namespace App\Livewire;

use App\Http\Controllers\AccountController;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Список замовлень з чипами «Усі / Активні · N / Повернення» (AccountOrders; DeskAccount) */
class AccountOrders extends Component
{
    #[Url(as: 'f', except: 'all')]
    public string $filter = 'all';

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'active', 'returns'], true) ? $filter : 'all';
    }

    public function render(): View
    {
        $customer = auth('customer')->user();
        abort_unless($customer, 403);

        $filter = in_array($this->filter, ['all', 'active', 'returns'], true) ? $this->filter : 'all';

        return view('livewire.account-orders', [
            'orders' => AccountController::filter($customer->orders()->getQuery(), $filter)->with('items.product.mainImage')->get(),
            'activeCount' => $customer->orders()->whereIn('status', AccountController::ACTIVE)->count(),
            'current' => $filter,
        ]);
    }
}
