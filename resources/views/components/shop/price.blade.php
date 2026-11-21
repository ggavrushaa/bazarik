{{-- Ціна: «799 ₴ 999 ₴», зі знижкою червона. size="l" — велика на сторінці товару; save — «Економія 400 ₴» --}}
@props(['product' => null, 'price' => null, 'old' => null, 'size' => null, 'save' => false, 'tag' => 'div'])
@php
    $price ??= $product?->price;
    $old ??= $product?->old_price;
    $sale = $old && $old > $price;
@endphp
<{{ $tag }} {{ $attributes->class(['bz-price', 'bz-price--sale' => $sale, 'bz-price--l' => $size === 'l']) }}>
    <span class="bz-price__now">{{ money($price) }}</span>
    @if ($sale)
        <s class="bz-price__old">{{ money($old) }}</s>
        @if ($save)<span class="bz-price__save">Економія {{ money($old - $price) }}</span>@endif
    @endif
</{{ $tag }}>
