<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Обране (AccountFavorites; десктоп — розділ кабінету).
 * Покупець — з кабінету (БД). Гість — товари з localStorage: Alpine передає $store.favorites.ids у show().
 */
class AccountFavorites extends Component
{
    /** @var array<int> */
    #[Locked]
    public array $ids = [];

    /** Гість: чи вже отримали ids з пристрою */
    #[Locked]
    public bool $loaded = false;

    public function show(array $ids = []): void
    {
        $this->ids = collect($ids)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->take(200)->values()->all();
        $this->loaded = true;
    }

    public function render(): View
    {
        $customer = auth('customer')->user();

        return view('livewire.account-favorites', [
            'customer' => $customer,
            'products' => $customer ? $this->forCustomer() : $this->forGuest(),
            'ready' => $customer || $this->loaded,
        ]);
    }

    private function forCustomer(): Collection
    {
        return auth('customer')->user()->favorites()->active()
            ->with(['mainImage', 'variants'])
            ->latest('favorites.created_at')
            ->get();
    }

    private function forGuest(): Collection
    {
        if ($this->ids === []) {
            return collect();
        }

        $products = Product::query()->active()->with(['mainImage', 'variants'])->whereIn('id', $this->ids)->get()->keyBy('id');

        // Як додавали: останні — першими
        return collect(array_reverse($this->ids))->filter(fn (int $id) => $products->has($id))->map(fn (int $id) => $products[$id])->values();
    }
}
