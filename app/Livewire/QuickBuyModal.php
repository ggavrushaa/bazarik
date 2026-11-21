<?php

namespace App\Livewire;

use App\Models\ProductVariant;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

/**
 * «Купити в 1 клік» модалкою поверх сторінки товару на десктопі: DeskQuickBuy / DeskQuickBuyUser.
 * Відкривається з будь-якого місця: Livewire.dispatch('open-quick-buy', { productId, variantId }).
 * Логіка замовлення — у QuickBuyForm (вона ж — сторінка /product/{slug}/quick-buy для телефона).
 */
class QuickBuyModal extends QuickBuyForm
{
    #[Locked]
    public bool $open = false;

    #[On('open-quick-buy')]
    public function openFor(int $productId, ?int $variantId = null): void
    {
        $variant = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->when($variantId, fn ($q) => $q->whereKey($variantId), fn ($q) => $q->orderByDesc('stock'))
            ->first();

        if (! $variant) {
            return;
        }

        $this->resetErrorBag();
        $this->placed = null;
        $this->variantId = $variant->id;
        $this->open = true;
        $this->prefill();
    }

    public function close(): void
    {
        $this->open = false;
        $this->placed = null;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.quick-buy-modal', $this->open ? $this->viewData() : []);
    }
}
