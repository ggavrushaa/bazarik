{{--
    Картка товару в списках (головна, каталог, пошук, крос-сел, обране) — розмітка з макетів.
    Для одягу кнопка «Обрати розмір» відкриває шторку/модалку вибору, фото й назва ведуть на сторінку товару.
    Потрібні зв'язки: mainImage, variants.

    :dots — кольорові крапки (мобільний каталог), :sizes — «Розміри S–XXL», size — розмір з активного фільтра.
--}}
@props(['product', 'dots' => false, 'sizes' => false, 'size' => null])
@php
    $url = route('product.show', $product);
    $inStock = $product->total_stock > 0;
    $colors = $product->is_apparel ? $product->variants->whereNotNull('color_hex')->unique('color')->pluck('color_hex') : collect();
    $sizeAvailable = $size && $product->variants->where('size', $size)->where('stock', '>', 0)->isNotEmpty();
@endphp
<article {{ $attributes->class('bz-card') }}>
    <div class="bz-card__media">
        {{-- Фото веде на сторінку товару; посилання для мишки, для клавіатури — назва нижче --}}
        <a href="{{ $url }}" tabindex="-1" aria-hidden="true" style="position: absolute; inset: 0">
            @if ($product->mainImage)
                <img src="{{ $product->mainImage->url }}" alt="{{ $product->mainImage->alt ?? $product->name }}" loading="lazy">
            @endif
        </a>
        <div class="bz-card__badges">
            @if ($product->discount_percent)<span class="bz-badge bz-badge--sale">−{{ $product->discount_percent }}%</span>@endif
            @if ($product->badge)<span class="bz-badge bz-badge--{{ $product->badge->value }}">{{ $product->badge->getLabel() }}</span>@endif
            @unless ($inStock)<span class="bz-badge">Немає в наявності</span>@endunless
        </div>
        <button class="bz-card__fav" type="button"
                x-data x-on:click="$store.favorites.toggle({{ $product->id }})"
                :aria-pressed="$store.favorites.has({{ $product->id }})"
                :aria-label="$store.favorites.has({{ $product->id }}) ? 'Прибрати з обраного' : 'Додати в обране'" aria-label="Додати в обране">
            <span><x-bz.icon name="heart" size="18" x-bind:fill="$store.favorites.has({{ $product->id }}) ? 'currentColor' : 'none'" /></span>
        </button>
    </div>
    <div class="bz-card__body">
        <a class="bz-card__title" href="{{ $url }}">{{ $product->name }}</a>
        @if ($dots && $colors->count() > 1)
            <div class="bz-dots" aria-label="{{ plural_uk($colors->count(), 'колір', 'кольори', 'кольорів') }}">
                @foreach ($colors as $hex)<i style="background: {{ $hex }}"></i>@endforeach
            </div>
        @endif
        @if ($product->reviews_count)
            <span class="bz-card__rating"><x-bz.icon name="star" />{{ rating($product->rating) }} · {{ plural_uk($product->reviews_count, 'відгук', 'відгуки', 'відгуків') }}</span>
        @endif
        @if ($sizes && $product->size_range)
            <span class="mut">Розміри {{ $product->size_range }}</span>
        @endif
        <x-shop.price :product="$product" />
        @if ($inStock)
            <span class="bz-delivery"><x-bz.icon name="truck" />{{ delivery_label() }}</span>
        @endif

        @if (! $inStock)
            <a class="bz-btn bz-btn--secondary bz-btn--block" href="{{ $url }}">Повідомити мене</a>
        @elseif ($product->is_apparel && $sizeAvailable)
            <button class="bz-btn bz-btn--primary bz-btn--block" type="button" x-data x-on:click="Livewire.dispatch('pick-product', { id: {{ $product->id }}, size: @js($size) })">В кошик · {{ $size }}</button>
        @elseif ($product->is_apparel)
            <button class="bz-btn bz-btn--secondary bz-btn--block" type="button" x-data x-on:click="Livewire.dispatch('pick-product', { id: {{ $product->id }} })">Обрати розмір</button>
        @else
            <button class="bz-btn bz-btn--primary bz-btn--block" type="button" x-data x-on:click="Livewire.dispatch('add-product', { id: {{ $product->id }} })">В кошик</button>
        @endif
    </div>
</article>
