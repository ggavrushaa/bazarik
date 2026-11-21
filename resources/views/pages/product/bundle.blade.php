{{-- «Разом дешевше»: цей товар + товар з набору зі знижкою (MobileProduct) --}}
@php
    $bundleOld = (float) $product->price + (float) $bundle->price;
    $bundlePrice = round($bundleOld * (100 - $product->bundle_discount) / 100);
    $bundleVariant = $bundle->variants->sortByDesc('stock')->first();
@endphp
<section class="pp-bundle">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px">
        <h2 style="margin: 0; font-size: 18px; line-height: 24px; font-weight: 800">Разом дешевше</h2>
        <span class="bz-badge bz-badge--sale">−{{ $product->bundle_discount }}%</span>
    </div>
    <div style="display: flex; align-items: center; gap: 8px">
        <div class="img pp-bundle__img">@if ($product->images->first())<img src="{{ $product->images->first()->url }}" alt="">@endif</div>
        <span style="font-size: 20px; font-weight: 800; color: var(--ink-muted)" aria-hidden="true">+</span>
        <a class="img pp-bundle__img" href="{{ route('product.show', $bundle) }}" tabindex="-1" aria-hidden="true">@if ($bundle->mainImage)<img src="{{ $bundle->mainImage->url }}" alt="">@endif</a>
        <div style="flex: 1; min-width: 0; font-size: 13px; line-height: 18px; font-weight: 600; color: var(--ink-2)">
            Цей товар + <a href="{{ route('product.show', $bundle) }}" style="color: var(--ink-2)">{{ $lcfirst($bundle->name) }}</a>
        </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px">
        <x-shop.price :price="$bundlePrice" :old="$bundleOld" />
        <button class="bz-btn bz-btn--dark" type="button"
                x-on:click="Livewire.dispatch('add-variant', { id: {{ $buyVariant->id }}, quantity: 1 }); Livewire.dispatch('add-variant', { id: {{ $bundleVariant->id }}, quantity: 1 })">Додати набір</button>
    </div>
</section>
