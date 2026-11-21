{{-- Кошик: MobileCart (до 768px) / DeskCart (від 768px); порожній — StateCartEmpty / DeskCartEmpty --}}
@php
    $count = $cart->count();
    $countLabel = plural_uk($count, 'товар', 'товари', 'товарів');
    $remaining = $cart->freeDeliveryRemaining();
    $freeDelivery = $remaining <= 0;
    $discount = $cart->discount();
@endphp
<div>
    @if ($lines->isEmpty())
        {{-- ── Порожній кошик ── --}}
        <div class="wrap flex flex-col gap-12" style="padding-top: 40px">
            <section class="mx-auto flex w-full flex-col items-center gap-[14px] text-center md:py-6" style="max-width: 560px">
                <svg width="200" height="150" viewBox="0 0 160 124" role="img" aria-label="Порожній прилавок під навісом">
                    <rect x="22" y="40" width="5" height="78" rx="2.5" fill="#1f1f24"></rect>
                    <rect x="133" y="40" width="5" height="78" rx="2.5" fill="#1f1f24"></rect>
                    <path d="M23.8 18.0H46.3L39.2 38.4A13.6 13.6 0 0 1 12.0 38.4Z" fill="#ff7a1a"></path>
                    <path d="M46.3 18.0H68.8L66.4 38.4A13.6 13.6 0 0 1 39.2 38.4Z" fill="#d95e0a"></path>
                    <path d="M68.8 18.0H91.2L93.6 38.4A13.6 13.6 0 0 1 66.4 38.4Z" fill="#ff7a1a"></path>
                    <path d="M91.2 18.0H113.7L120.8 38.4A13.6 13.6 0 0 1 93.6 38.4Z" fill="#d95e0a"></path>
                    <path d="M113.7 18.0H136.2L148.0 38.4A13.6 13.6 0 0 1 120.8 38.4Z" fill="#ff7a1a"></path>
                    <rect x="14" y="104" width="132" height="12" rx="4" fill="#1f1f24"></rect>
                    <rect x="40" y="96" width="80" height="4" rx="2" fill="#e7e7ec"></rect>
                </svg>
                <h1 class="mt-2 text-[26px] leading-8 md:mt-0 md:text-4xl md:leading-[42px]" style="margin-bottom: 0; font-family: var(--font-display); font-weight: 900">Прилавок поки порожній</h1>
                <p class="p only-mob" style="font-size: 15px; line-height: 22px">Додайте щось потрібне — завтра воно вже буде у вашому відділенні.</p>
                <p class="p only-desk">Додайте щось потрібне — завтра вже буде у вас.</p>
                <a class="bz-btn bz-btn--primary bz-btn--l bz-btn--block md:w-auto! md:px-8!" href="{{ $catalogUrl }}" style="margin-top: 8px">До каталогу</a>
                <a class="bz-btn bz-btn--ghost bz-btn--block only-mob" href="{{ route('home') }}#popular">Що беруть цього тижня</a>
            </section>

            @if ($popular->isNotEmpty())
                <section class="only-desk" style="display: flex; flex-direction: column; gap: 24px">
                    <div class="sechead"><h2 class="h2">Що беруть цього тижня</h2></div>
                    <div class="g4">
                        @foreach ($popular as $product)
                            <x-shop.product-card :product="$product" wire:key="pop-{{ $product->id }}" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @else
        <div class="wrap flex flex-col gap-5 pt-4 md:gap-14 md:pt-8">
            {{-- Заголовок --}}
            <h1 class="only-mob" style="margin: 0; font-size: 18px; line-height: 24px; font-weight: 800; text-align: center">Кошик · {{ $countLabel }}</h1>
            <div class="only-desk" style="display: flex; align-items: baseline; gap: 14px">
                <h1 class="h1" style="font-size: 40px; line-height: 46px">Кошик</h1>
                <span class="mut" style="font-size: 15px">{{ $countLabel }}</span>
            </div>

            <div class="grid items-start gap-5 md:-mt-6 md:gap-8 lg:grid-cols-[1fr_384px] lg:gap-12">
                <div class="flex flex-col">
                    {{-- Безкоштовна доставка --}}
                    @if ($freeDelivery)
                        <div style="padding: 14px 16px; border-radius: var(--radius-m); background: var(--success-soft); color: var(--success-strong); font-size: 15px; font-weight: 700">Доставка буде безкоштовною</div>
                    @else
                        <div class="flex flex-col gap-2 md:px-5 md:py-4" style="padding: 14px 16px; border-radius: var(--radius-m); background: var(--surface-warm)">
                            <span class="text-sm leading-5 md:text-[15px]" style="font-weight: 700">Додайте ще на {{ money($remaining) }} — доставка буде безкоштовною</span>
                            <x-shop.delivery-progress :value="$cart->freeDeliveryProgress()" />
                        </div>
                    @endif

                    {{-- Товари --}}
                    <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column">
                        @foreach ($lines as $line)
                            @php
                                $variant = $line['variant'];
                                $product = $line['product'];
                                $url = route('product.show', $product);
                                $atStock = $line['quantity'] >= $variant->stock;
                                $sale = $line['old_price'] && $line['old_price'] > $line['price'];
                            @endphp
                            <li wire:key="line-{{ $variant->id }}" class="grid grid-cols-[88px_1fr] gap-3 py-4 md:grid-cols-[120px_1fr_auto] md:gap-5 md:py-6" style="border-bottom: 1px solid var(--line)">
                                <a href="{{ $url }}" tabindex="-1" aria-hidden="true" class="img h-[88px] w-[88px] md:h-[120px] md:w-[120px]" style="border-radius: var(--radius-m)">
                                    @if ($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="">@endif
                                </a>

                                <div style="min-width: 0; display: flex; flex-direction: column; gap: 8px">
                                    <a href="{{ $url }}" class="text-sm leading-[19px] font-bold md:text-[17px] md:leading-[23px] md:font-extrabold" style="color: var(--ink); text-decoration: none">{{ $product->name }}</a>
                                    <span class="mut only-desk">{{ $variant->label ? $variant->label.' · ' : '' }}{{ $line['quantity'] }} шт</span>
                                    @if ($variant->label)<span class="mut only-mob">{{ $variant->label }}</span>@endif
                                    <span class="bz-delivery only-desk"><x-bz.icon name="truck" size="16" />{{ delivery_label() }}</span>

                                    {{-- Телефон: ціна, степер, «Видалити» --}}
                                    <x-shop.price class="only-mob" :price="$line['price']" :old="$line['old_price']" />
                                    <div class="only-mob" style="display: flex; justify-content: space-between; align-items: center; gap: 8px">
                                        <div class="bz-stepper" role="group" aria-label="Кількість">
                                            <button type="button" aria-label="Менше" wire:click="decrement({{ $variant->id }})" @disabled($line['quantity'] <= 1)><x-bz.icon name="minus" /></button>
                                            <output aria-live="polite">{{ $line['quantity'] }}</output>
                                            <button type="button" aria-label="Більше" wire:click="increment({{ $variant->id }})" @disabled($atStock)><x-bz.icon name="plus" /></button>
                                        </div>
                                        <button type="button" wire:click="remove({{ $variant->id }})" style="height: 44px; padding: 0 4px; border: 0; background: transparent; font: 700 14px/1 var(--font-sans); color: var(--ink-muted); text-decoration: underline; cursor: pointer">Видалити</button>
                                    </div>
                                    @if ($atStock && $variant->stock > 1)
                                        <span class="mut">Більше немає на складі</span>
                                    @endif

                                    {{-- Десктоп: «В обране», «Видалити» --}}
                                    <div class="only-desk" style="display: flex; gap: 20px; margin-top: auto; font-size: 14px; font-weight: 700">
                                        <button type="button" x-data x-on:click="$store.favorites.toggle({{ $product->id }})"
                                                style="display: inline-flex; gap: 6px; align-items: center; padding: 0; border: 0; background: transparent; font: inherit; color: var(--ink-2); cursor: pointer">
                                            <x-bz.icon name="heart" size="18" x-bind:fill="$store.favorites.has({{ $product->id }}) ? 'currentColor' : 'none'" />
                                            <span x-text="$store.favorites.has({{ $product->id }}) ? 'В обраному' : 'В обране'">В обране</span>
                                        </button>
                                        <button type="button" wire:click="remove({{ $variant->id }})"
                                                style="display: inline-flex; gap: 6px; align-items: center; padding: 0; border: 0; background: transparent; font: inherit; color: var(--ink-2); cursor: pointer">
                                            <x-bz.icon name="trash" size="18" />Видалити
                                        </button>
                                    </div>
                                </div>

                                {{-- Десктоп: ціна й кількість праворуч --}}
                                <div class="only-desk" style="display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between">
                                    <div style="display: flex; flex-direction: column; align-items: flex-end">
                                        <b class="tabular" style="font-size: 20px; font-weight: 800">{{ money($line['price']) }}</b>
                                        @if ($sale)<s class="mut">{{ money($line['old_price']) }}</s>@endif
                                    </div>
                                    <div class="qty" role="group" aria-label="Кількість">
                                        <button type="button" aria-label="Менше" wire:click="decrement({{ $variant->id }})" @disabled($line['quantity'] <= 1)><x-bz.icon name="minus" size="18" /></button>
                                        <span aria-live="polite">{{ $line['quantity'] }}</span>
                                        <button type="button" aria-label="Більше" wire:click="increment({{ $variant->id }})" @disabled($atStock)><x-bz.icon name="plus" size="18" /></button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Телефон: «Часто беруть разом» — компактний рядок --}}
                    @if ($crossSell->isNotEmpty())
                        @php $extra = $crossSell->first(); @endphp
                        <section class="only-mob" style="display: flex; flex-direction: column; gap: 12px; margin-top: 20px">
                            <h2 style="margin: 0; font-size: 18px; line-height: 24px; font-weight: 800">Часто беруть разом</h2>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: var(--radius-l); border: 1px solid var(--line)">
                                <a href="{{ route('product.show', $extra) }}" tabindex="-1" aria-hidden="true" class="img" style="width: 64px; height: 64px; flex: none; border-radius: var(--radius-m)">
                                    @if ($extra->mainImage)<img src="{{ $extra->mainImage->url }}" alt="">@endif
                                </a>
                                <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px">
                                    <a href="{{ route('product.show', $extra) }}" style="font-size: 14px; line-height: 19px; font-weight: 700; color: var(--ink); text-decoration: none">{{ $extra->name }}</a>
                                    <span class="tabular" style="font-size: 15px; font-weight: 800">{{ money($extra->price) }}</span>
                                </div>
                                <button class="bz-icon-btn" type="button" aria-label="Додати «{{ $extra->name }}» в кошик" style="background: var(--brand); color: var(--on-brand)"
                                        x-data x-on:click="Livewire.dispatch('{{ $extra->is_apparel ? 'pick-product' : 'add-product' }}', { id: {{ $extra->id }} })"><x-bz.icon name="plus" /></button>
                            </div>
                        </section>
                    @endif
                </div>

                {{-- Підсумок --}}
                <aside class="sum px-4! py-5! md:p-6! lg:sticky lg:top-6" aria-label="Підсумок">
                    <h2 class="h3 only-desk">Разом</h2>
                    <div class="row"><span>Товари ({{ $count }})</span><span class="tabular">{{ money($cart->fullPrice()) }}</span></div>
                    @if ($discount > 0)
                        <div class="row" style="font-weight: 700; color: var(--sale)"><span>Знижка</span><span class="tabular">−{{ money($discount) }}</span></div>
                    @endif
                    <div class="row"><span>Доставка</span><span>{{ $freeDelivery ? 'Безкоштовно' : 'за тарифом Нової Пошти' }}</span></div>
                    <div class="hr"></div>
                    <div style="display: flex; justify-content: space-between; align-items: baseline">
                        <span style="font-size: 18px; font-weight: 800"><span class="only-mob">Разом</span><span class="only-desk">До сплати</span></span>
                        <span class="tabular text-[28px] leading-8 md:text-[32px] md:leading-9" style="font-weight: 800">{{ money($cart->total()) }}</span>
                    </div>
                    <div class="bz-delivery bz-delivery--box" style="background: var(--bg)">
                        <x-bz.icon name="truck" size="22" />
                        <span>Отримаєте {{ delivery_label(lower: true) }}<small>якщо оформите до {{ config('shop.same_day_cutoff') }}</small></span>
                    </div>
                    <a class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" href="{{ route('checkout') }}">Оформити замовлення</a>
                    <span class="mut" style="text-align: center">Без реєстрації · оплата після огляду</span>
                </aside>
            </div>

            {{-- Десктоп: «Часто беруть разом» — картки --}}
            @if ($crossSell->isNotEmpty())
                <section class="only-desk" style="display: flex; flex-direction: column; gap: 24px; padding-bottom: 40px">
                    <div class="sechead"><h2 class="h2">Часто беруть разом</h2></div>
                    <div class="g4">
                        @foreach ($crossSell as $product)
                            <x-shop.product-card :product="$product" wire:key="xs-{{ $product->id }}" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif
</div>
