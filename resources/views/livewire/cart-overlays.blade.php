<div x-data x-on:keydown.escape.window="$wire.mode && $wire.close()">
    @if ($mode === 'pick' && $product)
        @php
            $variants = $product->variants->where('is_active', true)->values();
            $colors = $variants->whereNotNull('color')->unique('color')->map(fn ($v) => [
                'name' => $v->color,
                'hex' => $v->color_hex,
                'available' => $variants->where('color', $v->color)->where('stock', '>', 0)->isNotEmpty(),
            ])->values();
            $sizes = $variants->pluck('size')->filter()->unique()->values();
            $data = $variants->map(fn ($v) => ['id' => $v->id, 'color' => $v->color, 'size' => $v->size, 'stock' => $v->stock])->values();
            $firstColor = $colors->firstWhere('available', true)['name'] ?? null;
        @endphp
        <div x-data="sizePicker(@js($data), @js($firstColor))" x-on:picker-opened.window="preset($event.detail.size)">
            <div class="scrim" wire:click="close"></div>

            {{-- Телефон: шторка знизу (StateSizeSheet) --}}
            <div class="bz-sheet only-mob" role="dialog" aria-modal="true" aria-labelledby="sz-m">
                <div style="display: flex; flex-direction: column; gap: 16px">
                    <div style="display: flex; justify-content: center"><span style="width: 40px; height: 4px; border-radius: 99px; background: var(--line-strong)"></span></div>
                    <div style="display: flex; gap: 12px; align-items: center">
                        <div class="img" style="width: 64px; height: 64px; border-radius: 12px; flex: none">
                            @if ($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="">@endif
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 4px">
                            <span id="sz-m" style="font-size: 15px; font-weight: 800">{{ $product->name }}</span>
                            <span style="font-size: 15px; font-weight: 800" class="tabular">{{ money($product->price) }}</span>
                        </div>
                        <button class="bz-icon-btn" type="button" wire:click="close" aria-label="Закрити" style="background: transparent"><x-bz.icon name="close" size="22" /></button>
                    </div>
                    @include('livewire.partials.picker-options', ['id' => 'm'])
                    <button class="bz-btn bz-btn--primary bz-btn--l bz-btn--block" type="button" x-on:click="submit()" x-text="buttonText()">Обрати розмір</button>
                    <a href="{{ route('product.show', $product) }}" style="align-self: center; font-size: 14px; font-weight: 700">Усі деталі товару</a>
                </div>
            </div>

            {{-- Десктоп: модалка (DeskSizePicker) --}}
            <div class="bz-modal only-desk" role="dialog" aria-modal="true" aria-label="Вибір розміру" style="width: min(760px, calc(100vw - 48px)); display: grid; grid-template-columns: 300px 1fr; overflow: hidden">
                <div class="img" style="height: 100%; min-height: 440px">
                    @if ($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="{{ $product->mainImage->alt ?? $product->name }}">@endif
                </div>
                <div style="display: flex; flex-direction: column; gap: 20px; padding: 28px">
                    <div style="display: flex; justify-content: space-between; gap: 12px; align-items: flex-start">
                        <span style="display: flex; flex-direction: column; gap: 6px">
                            <b style="font-size: 20px; line-height: 26px; font-weight: 800">{{ $product->name }}</b>
                            <x-shop.price :product="$product" />
                        </span>
                        <button class="bz-icon-btn" type="button" wire:click="close" aria-label="Закрити" style="margin: -8px -8px 0 0"><x-bz.icon name="close" size="22" /></button>
                    </div>
                    @include('livewire.partials.picker-options', ['id' => 'd', 'desk' => true])
                    <button class="bz-btn bz-btn--primary bz-btn--l bz-btn--block" type="button" x-on:click="submit()" x-text="buttonText()">Обрати розмір</button>
                    <a href="{{ route('product.show', $product) }}" style="text-align: center; font-size: 15px; font-weight: 700">Усі деталі товару →</a>
                </div>
            </div>
        </div>
    @endif

    @if ($mode === 'added' && $product)
        @php
            $remaining = $cart->freeDeliveryRemaining();
            $label = $variant ? collect([$variant->color, $variant->size])->filter()->implode(' · ') : null;
            $shortName = $product->name;
        @endphp
        <div>
            <div class="scrim" wire:click="close"></div>

            {{-- Телефон: шторка (StateAddedToCart) --}}
            <div class="bz-sheet only-mob" role="dialog" aria-modal="true" aria-labelledby="added-m">
                <div style="display: flex; flex-direction: column; gap: 16px">
                    <div style="display: flex; justify-content: center"><span style="width: 40px; height: 4px; border-radius: 99px; background: var(--line-strong)"></span></div>
                    <div style="display: flex; align-items: center; gap: 10px">
                        <span style="width: 32px; height: 32px; border-radius: 99px; background: var(--success-soft); color: var(--success-strong); display: inline-flex; align-items: center; justify-content: center"><x-bz.icon name="check" size="18" stroke="2.6" /></span>
                        <h2 id="added-m" style="margin: 0; font-size: 18px; font-weight: 800">Додано в кошик</h2>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center">
                        <div class="img" style="width: 64px; height: 64px; border-radius: 12px; flex: none">
                            @if ($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="">@endif
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 4px">
                            <span style="font-size: 14px; font-weight: 700">{{ $shortName }}</span>
                            @if ($label)<span class="mut">{{ $label }}</span>@endif
                            <span style="font-size: 15px; font-weight: 800" class="tabular">{{ money($variant?->final_price ?? $product->price) }}</span>
                        </div>
                    </div>
                    @if ($remaining > 0)
                        <div style="padding: 12px 14px; border-radius: 12px; background: var(--surface-warm); font-size: 14px; font-weight: 700">Додайте ще на {{ money($remaining) }} — доставка буде безкоштовною</div>
                    @else
                        <div style="padding: 12px 14px; border-radius: 12px; background: var(--success-soft); color: var(--success-strong); font-size: 14px; font-weight: 700">Доставка буде безкоштовною</div>
                    @endif
                    <a class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" href="{{ route('cart') }}">Перейти до кошика</a>
                    <button class="bz-btn bz-btn--ghost bz-btn--block" type="button" wire:click="close">Продовжити покупки</button>
                </div>
            </div>

            {{-- Десктоп: правий drawer (DeskAddedToCart) --}}
            <aside class="bz-drawer only-desk" role="dialog" aria-modal="true" aria-label="Кошик" style="padding: 24px 28px; gap: 20px">
                <div style="display: flex; align-items: center; justify-content: space-between">
                    <span style="display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 800">
                        <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--success-soft); color: var(--success-strong); display: inline-flex; align-items: center; justify-content: center"><x-bz.icon name="check" size="18" stroke="2.6" /></span>Додано в кошик
                    </span>
                    <button class="bz-icon-btn" type="button" wire:click="close" aria-label="Закрити"><x-bz.icon name="close" size="22" /></button>
                </div>
                <div style="display: flex; gap: 14px; align-items: center">
                    <div class="img" style="width: 88px; height: 88px; border-radius: var(--radius-m); flex: none">
                        @if ($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="">@endif
                    </div>
                    <span style="flex: 1; display: flex; flex-direction: column; gap: 4px">
                        <b style="font-size: 15px; line-height: 20px">{{ $product->name }}</b>
                        <span class="mut">{{ collect([$variant?->color, $variant?->size, $quantity.' шт'])->filter()->implode(' · ') }}</span>
                    </span>
                    <b style="font-size: 17px" class="tabular">{{ money($variant?->final_price ?? $product->price) }}</b>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; padding: 16px; border-radius: var(--radius-m); background: {{ $remaining > 0 ? 'var(--surface-warm)' : 'var(--success-soft)' }}">
                    <span style="font-size: 14px; font-weight: 700; {{ $remaining > 0 ? '' : 'color: var(--success-strong)' }}">
                        {{ $remaining > 0 ? 'Додайте ще на '.money($remaining).' — доставка буде безкоштовною' : 'Доставка буде безкоштовною' }}
                    </span>
                    <x-shop.delivery-progress :value="$cart->freeDeliveryProgress()" />
                </div>
                <div class="hr"></div>
                <div class="row"><span>У кошику {{ plural_uk($cart->count(), 'товар', 'товари', 'товарів') }}</span><b style="color: var(--ink); font-size: 17px" class="tabular">{{ money($cart->total()) }}</b></div>
                <div style="display: flex; flex-direction: column; gap: 10px">
                    <a class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" href="{{ route('cart') }}">Перейти до кошика</a>
                    <button class="bz-btn bz-btn--secondary bz-btn--l bz-btn--block" type="button" wire:click="close">Продовжити покупки</button>
                </div>
                @if ($related->isNotEmpty())
                    <div style="margin-top: auto; display: flex; flex-direction: column; gap: 12px">
                        <b style="font-size: 16px; font-weight: 800">До цього товару беруть</b>
                        @foreach ($related as $item)
                            <a href="{{ route('product.show', $item) }}" style="display: flex; align-items: center; gap: 12px; color: var(--ink); text-decoration: none">
                                <div class="img" style="width: 56px; height: 56px; border-radius: 10px; flex: none">@if ($item->mainImage)<img src="{{ $item->mainImage->url }}" alt="">@endif</div>
                                <span style="flex: 1; font-size: 14px; line-height: 19px; font-weight: 700">{{ $item->name }}</span>
                                <b style="font-size: 15px" class="tabular">{{ money($item->price) }}</b>
                            </a>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    @endif
</div>
