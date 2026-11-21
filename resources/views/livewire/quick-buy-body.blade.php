{{--
    Вміст «Купити в 1 клік» — спільний для сторінки (телефон: QuickBuyGuest / QuickBuyUser)
    і модалки (десктоп: DeskQuickBuy / DeskQuickBuyUser). $desk — розмітка десктопної модалки.
--}}
@php
    $desk ??= false;
    $id = $desk ? 'qbd' : 'qbm';
    $variantLine = $variant ? collect([$variant->color, $variant->size])->filter()->implode(' · ') : null;
    $price = $variant ? money($variant->final_price) : null;
    $changeUrl = link_to_route_or_hash('account.addresses');
    $loginUrl = route('login');
    $authModal = class_exists(\App\Livewire\AuthModal::class);
    $title = $order ? 'Дякуємо, замовлення прийнято' : 'Купити в 1 клік';
@endphp

{{-- Заголовок --}}
@if ($desk)
    <div style="display: flex; justify-content: space-between; align-items: flex-start">
        <h2 class="h1" id="{{ $id }}-title" style="font-size: 28px; line-height: 34px">{{ $title }}</h2>
        <button class="bz-icon-btn" type="button" wire:click="close" aria-label="Закрити" style="margin: -8px -8px 0 0"><x-bz.icon name="close" size="22" /></button>
    </div>
@else
    <h1 id="{{ $id }}-title" style="margin: 0; font-size: 20px; font-weight: 800">{{ $title }}</h1>
@endif

@if ($product)
    {{-- Товар --}}
    @if ($desk)
        <div style="display: flex; gap: 14px; align-items: center; padding: 12px; border-radius: var(--radius-m); background: var(--surface)">
            <div class="img" style="width: 64px; height: 64px; border-radius: 10px; flex: none">@if ($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="">@endif</div>
            <span style="flex: 1; display: flex; flex-direction: column"><b style="font-size: 15px">{{ $product->name }}</b>@if ($variantLine)<span class="mut">{{ $variantLine }}</span>@endif</span>
            <b style="font-size: 17px" class="tabular">{{ $price }}</b>
        </div>
    @else
        <div style="display: flex; gap: 12px; align-items: center">
            <span class="img" style="width: 56px; height: 56px; border-radius: 10px; flex: none">@if ($product->mainImage)<img src="{{ $product->mainImage->url }}" alt="">@endif</span>
            <span style="flex: 1; display: flex; flex-direction: column"><span style="font-size: 14px; font-weight: 700">{{ $product->name }}</span>@if ($variantLine)<span class="mut">{{ $variantLine }}</span>@endif</span>
            <b style="font-size: 16px" class="tabular">{{ $price }}</b>
        </div>
    @endif
@endif

@if ($order)
    {{-- Заявку гостя прийнято: доставку уточнить менеджер --}}
    <div style="display: flex; gap: 12px; align-items: flex-start">
        <span style="width: 32px; height: 32px; flex: none; border-radius: 99px; background: var(--success-soft); color: var(--success-strong); display: inline-flex; align-items: center; justify-content: center"><x-bz.icon name="check" size="18" stroke="2.6" /></span>
        <p class="p" style="font-size: {{ $desk ? 15 : 14 }}px; line-height: {{ $desk ? 22 : 21 }}px">
            № {{ $order->number }}. Менеджер передзвонить на <b style="white-space: nowrap">{{ \App\Support\Phone::format($order->phone) }}</b>, уточнить відділення й оформить замовлення. Графік: {{ config('shop.hours') }}.
        </p>
    </div>
    @if ($desk)
        <button class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" type="button" wire:click="close">Продовжити покупки</button>
    @else
        <a class="bz-btn bz-btn--primary bz-btn--l bz-btn--block" href="{{ route('product.show', $product) }}">Продовжити покупки</a>
    @endif
@elseif (! $product || ! $available)
    {{-- Закінчився, поки людина думала --}}
    <p class="p" role="alert" style="color: var(--sale); font-weight: 700; font-size: 15px">
        {{ $errors->first('stock') ?: 'Цей варіант закінчився. Оберіть інший колір чи розмір або підпишіться на надходження.' }}
    </p>
    @if ($product)
        <a class="bz-btn bz-btn--secondary bz-btn--l bz-btn--block" href="{{ route('product.show', $product) }}">До товару</a>
    @endif
@elseif ($address)
    {{-- Покупець з акаунтом: збережені отримувач, відділення й оплата --}}
    @php
        $rows = [
            ['Отримувач', $address->recipient_name],
            ['Доставка', $addressLine],
            ['Оплата', \App\Enums\PaymentMethod::CashOnDelivery->getLabel()],
            ['Отримаєте', delivery_label(lower: true)],
        ];
    @endphp
    @if ($desk)
        <div>
            @foreach ($rows as [$label, $value])
                <div class="row" style="padding: 12px 0; border-bottom: 1px solid var(--line)"><span>{{ $label }}</span><b style="color: var(--ink); font-weight: 800; text-align: right">{{ $value }}</b></div>
            @endforeach
        </div>
    @else
        <section style="display: flex; flex-direction: column; gap: 10px; padding: 14px 16px; border-radius: 16px; background: var(--surface)">
            @foreach ($rows as [$label, $value])
                <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px"><span style="color: var(--ink-muted); font-weight: 600">{{ $label }}</span><b style="text-align: right">{{ $value }}</b></div>
            @endforeach
        </section>
    @endif
    <a href="{{ $changeUrl }}" style="font-size: 14px; font-weight: 700">Змінити доставку чи оплату</a>
    @error('stock')<p class="bz-hint" role="alert" style="margin: 0; color: var(--sale); font-weight: 700">{{ $message }}</p>@enderror
    <button @class(['bz-btn', 'bz-btn--l', 'bz-btn--block', 'bz-btn--dark' => $desk, 'bz-btn--primary' => ! $desk]) type="button"
            wire:click="place" wire:loading.attr="disabled" wire:target="place">Підтвердити · {{ $price }}</button>
@else
    {{-- Гість: ім'я + телефон, менеджер передзвонить --}}
    <form wire:submit="place" style="display: contents" novalidate>
        <p @class(['p' => $desk]) style="margin: 0; {{ $desk ? 'font-size: 15px; line-height: 22px' : 'font-size: 14px; line-height: 21px; font-weight: 500; color: var(--ink-2)' }}">Залиште номер — менеджер передзвонить за 10 хвилин, уточнить відділення й оформить замовлення.</p>
        <div @if ($desk) style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: start" @else style="display: contents" @endif>
            <div @class(['bz-field', 'bz-field--error' => $errors->has('name')])>
                <label class="bz-label" for="{{ $id }}-name">Ім’я</label>
                <input class="bz-input" id="{{ $id }}-name" type="text" autocomplete="name" maxlength="120" wire:model="name"
                       @if ($errors->has('name')) aria-invalid="true" aria-describedby="{{ $id }}-name-err" @endif>
                @error('name')<span class="bz-hint" id="{{ $id }}-name-err" style="color: var(--sale)">{{ $message }}</span>@enderror
            </div>
            <div @class(['bz-field', 'bz-field--error' => $errors->has('phone')])>
                <label class="bz-label" for="{{ $id }}-phone">Телефон</label>
                <div class="bz-input-wrap"><span class="bz-prefix" aria-hidden="true">+380</span><input id="{{ $id }}-phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="67 123 45 67" maxlength="20" wire:model.blur="phone"
                       @if ($errors->has('phone')) aria-invalid="true" aria-describedby="{{ $id }}-phone-err" @endif></div>
                @error('phone')<span class="bz-hint" id="{{ $id }}-phone-err" style="color: var(--sale)">{{ $message }}</span>@enderror
            </div>
        </div>
        @error('stock')<p class="bz-hint" role="alert" style="margin: 0; color: var(--sale); font-weight: 700">{{ $message }}</p>@enderror
        <button @class(['bz-btn', 'bz-btn--l', 'bz-btn--block', 'bz-btn--dark' => $desk, 'bz-btn--primary' => ! $desk]) type="submit"
                wire:loading.attr="disabled" wire:target="place">Замовити</button>
        @guest('customer')
            <a href="{{ $loginUrl }}" @if ($desk && $authModal) x-on:click.prevent="$wire.close(); Livewire.dispatch('open-auth')" @endif
               style="{{ $desk ? 'text-align: center' : 'align-self: center' }}; font-size: 14px; font-weight: 700">Увійти — і замовлення оформиться без дзвінка</a>
        @endguest
    </form>
@endif
