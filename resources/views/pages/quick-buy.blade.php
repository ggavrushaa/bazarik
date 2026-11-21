{{-- «Купити в 1 клік» на телефоні (і без JS): QuickBuyGuest / QuickBuyUser. На десктопі — модалка QuickBuyModal. --}}
<x-layouts.app title="Купити в 1 клік · {{ $product->name }}">
    <div class="wrap" style="padding-top: 12px; padding-bottom: 24px">
        <div style="max-width: 520px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px">
            <a href="{{ route('product.show', $product) }}" style="display: inline-flex; align-items: center; gap: 4px; font-size: 14px; font-weight: 700"><x-bz.icon name="back" size="18" />До товару</a>
            <livewire:quick-buy-form :variant-id="$variant?->id" />
        </div>
    </div>
</x-layouts.app>
