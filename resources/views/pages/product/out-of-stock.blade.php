{{-- Немає в наявності: StateOutOfStock (телефон) / DeskOutOfStock (десктоп) --}}
@php($image = $images->first())
<div class="wrap pp pp--oos">
    <div class="only-desk">@include('pages.product.crumbs')</div>

    <section class="pp-oos">
        <div class="img pp-oos__img">
            @if ($image)<img src="{{ $image->url }}" alt="{{ $image->alt ?? $product->name }}">@endif
            <span class="bz-badge only-mob" style="position: absolute; top: 12px; left: 12px">Немає в наявності</span>
        </div>

        <div class="pp-oos__info">
            <span class="pp-oos__pill only-desk">Немає в наявності</span>
            <h1 class="pp-title">{{ $product->name }}</h1>
            @if ($product->reviews_count)
                <span class="bz-card__rating pp-rating only-desk"><x-bz.icon name="star" />{{ rating($product->rating) }} · {{ $reviewsWord($product->reviews_count) }}</span>
            @endif
            <div class="bz-price bz-price--l"><span class="bz-price__now" style="color: var(--ink-muted)">{{ money($product->price) }}</span></div>

            <section class="pp-notify" aria-labelledby="pp-notify-title">
                <b id="pp-notify-title" class="pp-notify__title"><x-bz.icon name="bell" size="22" />Повідомимо, коли з’явиться</b>
                <p class="pp-notify__text">Надішлемо SMS — і відкладемо один для вас на добу.</p>
                <livewire:product-notify :product-id="$product->id" />
            </section>

            <button class="bz-btn bz-btn--secondary bz-btn--l only-desk" type="button" style="align-self: flex-start; gap: 8px"
                    x-data x-on:click="$store.favorites.toggle({{ $product->id }})" :aria-pressed="$store.favorites.has({{ $product->id }})">
                <x-bz.icon name="heart" size="20" x-bind:fill="$store.favorites.has({{ $product->id }}) ? 'currentColor' : 'none'" />
                <span x-text="$store.favorites.has({{ $product->id }}) ? 'В обраному' : 'Додати в обране'">Додати в обране</span>
            </button>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="pp-related">
            <div class="sechead"><h2 class="h2 pp-h2">Схожі в наявності</h2></div>
            <div class="g4">
                @foreach ($related as $item)
                    <x-shop.product-card :product="$item" :sizes="$item->is_apparel" />
                @endforeach
            </div>
        </section>
    @endif
</div>
