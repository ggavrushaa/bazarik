{{--
    Блок покупки. Телефон: MobileProduct / MobileApparel (у одягу кнопка — лише в нижній панелі).
    Десктоп: права колонка DeskProduct.
--}}
@php
    $cutoff = config('shop.same_day_cutoff');
    $deliveryLine = 'Замовте до '.$cutoff.' — отримаєте '.delivery_label(lower: true);
    $returnDays = (int) config('shop.return_days');
    $returnWord = plural_uk($returnDays, 'день', 'дні', 'днів', false);
    $freeFrom = config('shop.free_delivery_from');
    $deskDeliveryNote = $product->price >= $freeFrom
        ? 'Доставка у відділення безкоштовна'
        : 'Відділення ≈ '.money(config('shop.delivery_cost')).' · безкоштовно від '.money($freeFrom);
    $quickBuyUrl = link_to_route_or_hash('quick-buy', $product);
    $stock = $product->total_stock;
    $favBtn = 'x-data x-on:click="$store.favorites.toggle('.$product->id.')" :aria-pressed="$store.favorites.has('.$product->id.')"';
@endphp
<div @class(['pp-buy', 'pp-buy--apparel' => $apparel])>
    {{-- Назва, рейтинг, ціна --}}
    <div class="pp-head">
        @if ($product->discount_percent || $product->badge)
            <div class="only-desk" style="display: flex; gap: 6px">
                @if ($product->discount_percent)<span class="bz-badge bz-badge--sale">−{{ $product->discount_percent }}%</span>@endif
                @if ($product->badge)<span class="bz-badge bz-badge--{{ $product->badge->value }}">{{ $product->badge->getLabel() }}</span>@endif
            </div>
        @endif
        <h1 class="pp-title">{{ $product->name }}</h1>
        <div class="pp-meta">
            @if ($product->reviews_count || $sku)
                <span class="bz-card__rating pp-rating">
                    @if ($product->reviews_count)
                        <x-bz.icon name="star" />{{ rating($product->rating) }} · <a href="#reviews">{{ $reviewsWord($product->reviews_count) }}</a>
                    @endif
                    @if ($sku)<span class="only-desk">@if ($product->reviews_count)&nbsp;·&nbsp;@endif Артикул {{ $sku }}</span>@endif
                </span>
            @endif
            @unless ($apparel)
                @if ($stock <= 3)
                    <span class="bz-badge bz-badge--low">Залишилось {{ $stock }} шт</span>
                @else
                    <span class="bz-badge bz-badge--stock">В наявності</span>
                @endif
            @endunless
        </div>
        @if ($apparel)
            <x-shop.price :product="$product" size="l" />
        @endif
    </div>

    @unless ($apparel)
        <x-shop.price :product="$product" size="l" :save="true" />

        {{-- Телефон: доставка одразу під ціною --}}
        <div class="bz-delivery bz-delivery--box only-mob">
            <x-bz.icon name="truck" />
            <span>{{ $deliveryLine }}<small>Відділення, поштомат або кур’єр Нової Пошти</small></span>
        </div>
    @endunless

    @if ($apparel)
        {{-- Колір --}}
        @if ($colors->isNotEmpty())
            <section class="pp-group">
                <div class="bz-option-label" id="pp-color"><span>Колір: <b x-text="color">{{ $firstColor }}</b></span></div>
                <div class="bz-swatches" role="group" aria-labelledby="pp-color">
                    @foreach ($colors as $c)
                        <button class="bz-swatch" type="button" x-on:click="setColor(@js($c['name']))"
                                :aria-pressed="color === @js($c['name'])" aria-pressed="{{ $c['name'] === $firstColor ? 'true' : 'false' }}"
                                aria-label="{{ $c['name'] }}{{ $c['available'] ? '' : ', немає в наявності' }}" @disabled(! $c['available'])>
                            <span style="background: {{ $c['hex'] }}"></span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Розмір --}}
        <section class="pp-group" id="sizes">
            <div class="bz-option-label" id="pp-size">
                <span>Розмір: <b x-text="size ?? 'не обрано'">не обрано</b></span>
                @if ($product->size_chart)
                    <button type="button" class="pp-chart-toggle only-mob" x-on:click="chart = !chart" :aria-expanded="chart" aria-controls="pp-chart-mob">Таблиця розмірів</button>
                    <a class="only-desk" href="#chart" style="font-size: 14px; font-weight: 700">Таблиця розмірів</a>
                @endif
            </div>
            <div class="bz-sizes" role="group" aria-labelledby="pp-size">
                @foreach ($sizes as $s)
                    @php($n = $stockOf($s))
                    <button class="bz-size" type="button" x-on:click="size = @js($s); needSize = false"
                            :aria-pressed="size === @js($s)" aria-pressed="false"
                            :disabled="stockOf(@js($s)) === 0" @disabled($n === 0)
                            :aria-label="sizeLabel(@js($s))" aria-label="{{ $n === 0 ? "$s, немає в наявності" : ($n <= 3 ? "$s, залишилось $n" : $s) }}">
                        {{ $s }}<small x-show="stockOf(@js($s)) > 0 && stockOf(@js($s)) <= 3" x-text="stockOf(@js($s)) + ' шт'" @if ($n === 0 || $n > 3) style="display: none" @endif>{{ $n }} шт</small>
                    </button>
                @endforeach
            </div>
            <p class="bz-hint" role="alert" x-show="needSize" x-cloak style="margin: 0; color: var(--sale); font-weight: 700">Оберіть розмір, щоб додати в кошик</p>
            @if ($product->fit_hint)
                <div class="bz-fit"><x-bz.icon name="check" size="18" />{{ $product->fit_hint }}</div>
            @endif
            @if ($product->model_note)
                <span class="mut">{{ $product->model_note }}</span>
            @endif

            @if ($product->size_chart)
                <div class="only-mob" id="pp-chart-mob" x-show="chart" x-cloak x-transition.opacity>
                    @include('pages.product.size-chart')
                </div>
            @endif
        </section>
    @endif

    {{-- Кнопки. Телефон-одяг — лише нижня панель (bz-buybar) --}}
    <div @class(['pp-actions', 'only-desk' => $apparel])>
        @if ($apparel)
            <button class="bz-btn bz-btn--l" type="button" style="flex: 1"
                    :class="size ? 'bz-btn--primary' : 'bz-btn--secondary'" x-on:click="buy()" x-text="buttonText()">Оберіть розмір</button>
        @else
            <button class="bz-btn bz-btn--primary bz-btn--l" type="button" style="flex: 1"
                    x-on:click="Livewire.dispatch('add-variant', { id: {{ $buyVariant->id }}, quantity: 1 })">
                <x-bz.icon name="cart" class="only-mob" />Додати в кошик
            </button>
        @endif
        <button class="bz-btn bz-btn--secondary bz-btn--l only-desk" type="button" style="width: 52px; padding: 0" {!! $favBtn !!}
                :aria-label="$store.favorites.has({{ $product->id }}) ? 'Прибрати з обраного' : 'Додати в обране'" aria-label="Додати в обране">
            <x-bz.icon name="heart" size="22" x-bind:fill="$store.favorites.has({{ $product->id }}) ? 'currentColor' : 'none'" />
        </button>
    </div>
    @unless ($apparel)
        <a class="bz-btn bz-btn--secondary bz-btn--block only-mob" href="{{ $quickBuyUrl }}?variant={{ $buyVariant->id }}">Купити в 1 клік</a>
    @endunless
    {{-- Десктоп: модалка QuickBuyModal поверх товару; без JS — сторінка. Одяг — спершу розмір --}}
    @if ($apparel)
        <a class="bz-btn bz-btn--ghost bz-btn--block only-desk" href="{{ $quickBuyUrl }}" style="margin-top: -12px"
           :href="current() ? @js($quickBuyUrl.'?variant=') + current().id : @js($quickBuyUrl)"
           x-on:click.prevent="const v = current(); if (!size || !v || v.stock === 0) { needSize = true; document.getElementById('sizes')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; } Livewire.dispatch('open-quick-buy', { productId: {{ $product->id }}, variantId: v.id })">Купити в 1 клік</a>
    @else
        <a class="bz-btn bz-btn--ghost bz-btn--block only-desk" href="{{ $quickBuyUrl }}?variant={{ $buyVariant->id }}" style="margin-top: -12px"
           x-on:click.prevent="Livewire.dispatch('open-quick-buy', { productId: {{ $product->id }}, variantId: {{ $buyVariant->id }} })">Купити в 1 клік</a>
    @endif

    {{-- Доставка: у одягу на телефоні — після розмірів; на десктопі — після кнопок --}}
    <div @class(['bz-delivery', 'bz-delivery--box', 'only-desk' => ! $apparel])>
        <x-bz.icon name="truck" />
        <span>{{ $deliveryLine }}
            <small>
                @if ($apparel)<span class="only-mob">Не підійшов розмір — повернете у відділенні протягом {{ $returnDays }} {{ $returnWord }}</span>@endif
                <span class="only-desk">{{ $deskDeliveryNote }}</span>
            </small>
        </span>
    </div>

    {{-- Обіцянки --}}
    @unless ($apparel)
        <div class="pp-promises only-mob" aria-label="Наші обіцянки">
            <div><x-bz.icon name="truck" size="22" /><b>{{ \Illuminate\Support\Str::before(delivery_label(), ',') }}</b><span>у відділенні</span></div>
            <div><x-bz.icon name="cash" size="22" /><b>Оплата</b><span>після огляду</span></div>
            <div><x-bz.icon name="return" size="22" /><b>{{ $returnDays }} {{ $returnWord }}</b><span>на повернення</span></div>
        </div>
    @endunless
    <ul class="pp-promise-list only-desk">
        <li><x-bz.icon name="cash" size="20" /><span><b>Оплата після огляду</b> — відкриваєте у відділенні й тоді платите</span></li>
        @if ($apparel)
            <li><x-bz.icon name="return" size="20" /><span><b>{{ $returnDays }} {{ $returnWord }} на обмін</b> — не підійшов розмір — повернете у відділенні</span></li>
        @else
            <li><x-bz.icon name="return" size="20" /><span><b>{{ $returnDays }} {{ $returnWord }} на повернення</b> — не підійшло — повернете у відділенні</span></li>
            <li><x-bz.icon name="shield" size="20" /><span><b>Гарантія {{ config('shop.warranty_months') }} міс</b> — зламається — замінимо або повернемо гроші</span></li>
        @endif
    </ul>

    @if ($bundle)
        <div class="only-desk">@include('pages.product.bundle')</div>
    @endif
</div>
