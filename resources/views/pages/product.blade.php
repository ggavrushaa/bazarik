{{--
    Сторінка товару.
    Звичайний товар — MobileProduct, одяг — MobileApparel, від 768px — DeskProduct (для звичайного товару
    замість блоків одягу — «Простими словами», характеристики, «Разом дешевше»).
    Немає залишку — StateOutOfStock / DeskOutOfStock.

    Стан вибору (фото, колір, розмір, таблиця розмірів, вкладки) — один Alpine-компонент на всю сторінку,
    щоб мобільна й десктопна розмітка, таблиця розмірів і нижня панель бачили той самий розмір.
--}}
@php

    $variants = $product->variants->values();
    $apparel = $product->is_apparel && $variants->whereNotNull('size')->isNotEmpty();
    $category = $product->category;
    $images = $product->images;
    $reviewsWord = fn (int $n) => plural_uk($n, 'відгук', 'відгуки', 'відгуків');
    $lcfirst = fn (string $s) => mb_strtolower(mb_substr($s, 0, 1)).mb_substr($s, 1);

    // Артикул — спільна частина SKU варіантів («BZ-10482-OLI-M», «BZ-10482-GRA-S» → «BZ-10482»)
    $skus = $variants->pluck('sku')->filter()->values();
    $sku = $skus->reduce(function (?string $prefix, string $s) {
        if ($prefix === null) {
            return $s;
        }
        $i = 0;
        while ($i < min(strlen($prefix), strlen($s)) && $prefix[$i] === $s[$i]) {
            $i++;
        }

        return substr($prefix, 0, $i);
    });
    $sku = $sku ? rtrim($sku, '-') : null;

    // Дані для sizePicker (resources/js/app.js)
    $colors = $variants->whereNotNull('color')->unique('color')->map(fn ($v) => [
        'name' => $v->color,
        'hex' => $v->color_hex,
        'available' => $variants->where('color', $v->color)->where('stock', '>', 0)->isNotEmpty(),
    ])->values();
    $order = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
    $sizes = $variants->pluck('size')->filter()->unique()
        ->sortBy(fn ($s) => ($i = array_search($s, $order, true)) === false ? 99 : $i)->values();
    $pickerData = $variants->map(fn ($v) => ['id' => $v->id, 'color' => $v->color, 'size' => $v->size, 'stock' => $v->stock])->values();
    $firstColor = $colors->firstWhere('available', true)['name'] ?? null;
    $stockOf = fn (string $size) => (int) ($variants->first(fn ($v) => (! $firstColor || $v->color === $firstColor) && $v->size === $size)?->stock ?? 0);

    // Звичайний товар: варіант, який додаємо в кошик
    $buyVariant = $variants->sortByDesc('stock')->first();

    $seoDescription = $product->seo_description
        ?? $product->short_description
        ?? \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 160)
        ?: null;
@endphp
<x-layouts.app :title="$product->seo_title ?? $product->name" :description="$seoDescription" :category="$category">
    @include('pages.product.styles')

    @if (! $inStock)
        @include('pages.product.out-of-stock')
    @else
        <div class="wrap pp"
             x-data="{
                 @if ($apparel) ...sizePicker(@js($pickerData), @js($firstColor)), chart: false, @endif
                 photo: 0,
                 tab: 'desc',
                 go(i) {
                     this.photo = i;
                     const track = this.$refs.track;
                     if (track) track.scrollTo({ left: track.clientWidth * i, behavior: 'smooth' });
                 },
                 @if ($apparel)
                 buy(scroll = false) {
                     this.submit();
                     if (this.needSize && scroll) document.getElementById('sizes')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 },
                 @endif
             }">
            @include('pages.product.crumbs')

            <section class="pp-top">
                @include('pages.product.gallery')
                @include('pages.product.buy-box')
            </section>

            @include('pages.product.details')

            @if ($bundle)
                <div class="only-mob">@include('pages.product.bundle')</div>
            @endif

            @include('pages.product.reviews')

            @if ($related->isNotEmpty())
                <section class="pp-related">
                    <div class="sechead"><h2 class="h2 pp-h2">Часто беруть разом</h2></div>
                    <div class="g4">
                        @foreach ($related as $item)
                            <x-shop.product-card :product="$item" :sizes="$item->is_apparel" />
                        @endforeach
                    </div>
                </section>
            @endif

            @include('pages.product.buybar')
        </div>
    @endif

    @php
        $ld = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $sku,
            'image' => $images->map(fn ($img) => $img->url)->all(),
            'description' => $seoDescription,
            'aggregateRating' => $product->reviews_count ? ['@type' => 'AggregateRating', 'ratingValue' => (float) $product->rating, 'reviewCount' => $product->reviews_count] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product.show', $product),
                'priceCurrency' => config('shop.currency'),
                'price' => (float) $product->price,
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ]);
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</x-layouts.app>
