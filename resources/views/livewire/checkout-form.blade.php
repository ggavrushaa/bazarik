{{-- Оформлення: MobileCheckout (до 768px) / DeskCheckout (від 768px). Шапка — <x-shop.checkout-header> з макета --}}
@php
    $count = $cart->count();
    $courier = $delivery === 'np_courier';
    $locker = $delivery === 'np_locker';
    $cod = $payment === 'cod';
    $fieldset = 'margin: 0; min-width: 0; border-color: var(--line); display: flex; flex-direction: column';
    $stepNo = 'width: 32px; height: 32px; flex: none; border-radius: 50%; background: var(--ink); color: var(--on-ink); display: inline-flex; align-items: center; justify-content: center; font-size: 15px';
    $payments = [
        'wallet' => ['Apple Pay / Google Pay', 'Швидко, без введення картки', 'Apple / Google Pay', 'Без введення картки'],
        'card' => ['Карткою онлайн', 'Visa, Mastercard', 'Карткою онлайн', 'Visa, Mastercard'],
        'cod' => ['Оплата при отриманні', 'Оглядаєте у відділенні й тоді платите', 'При отриманні', 'Оглядаєте й платите'],
    ];
    $deliveryValue = fn (?float $cost) => $cost === null ? 'Безкоштовно' : '≈ '.money($cost);
@endphp
<div class="wrap flex flex-col gap-12 pt-4 md:pt-10">
    <h1 class="h1 only-desk" style="font-size: 40px; line-height: 46px">Оформлення замовлення</h1>

    <div class="grid items-start gap-7 pb-10 md:-mt-6 md:gap-10 md:pb-[72px] lg:grid-cols-[1fr_400px]">
        <form id="checkout" wire:submit="place" novalidate class="flex flex-col gap-7 md:gap-5">
            <p class="only-mob" style="margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">Без реєстрації — займе хвилину.</p>
            <div class="only-desk" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-radius: var(--radius-m); background: var(--surface)">
                <span style="font-size: 15px; font-weight: 600">Без реєстрації — займе хвилину. Вже купували?</span>
                <a href="{{ link_to_route_or_hash('login') }}" style="font-size: 15px; font-weight: 800">Увійти за номером</a>
            </div>

            @error('cart')
                <div role="alert" style="padding: 14px 16px; border-radius: var(--radius-m); background: var(--warning-soft); color: var(--sale); font-size: 15px; line-height: 22px; font-weight: 700">
                    @foreach ($errors->get('cart') as $message)<p style="margin: 0">{{ $message }}</p>@endforeach
                    <a href="{{ route('cart') }}" style="color: inherit">Перевірити кошик</a>
                </div>
            @enderror

            {{-- 1. Контакти --}}
            <fieldset class="gap-4 border-0 p-0 md:rounded-(--radius-xl) md:border md:p-7" style="{{ $fieldset }}">
                <legend class="md:sr-only" style="padding: 0; margin-bottom: 16px; font-size: 18px; line-height: 24px; font-weight: 800">1. Контакти</legend>
                <div class="only-desk" style="display: flex; align-items: center; gap: 12px; font-size: 20px; line-height: 26px; font-weight: 800" aria-hidden="true"><span style="{{ $stepNo }}">1</span>Контакти</div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div @class(['bz-field', 'bz-field--error' => $errors->has('phone')])>
                        <label class="bz-label" for="co-phone">Телефон</label>
                        <div class="bz-input-wrap"><span class="bz-prefix" aria-hidden="true">+380</span><input id="co-phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="67 123 45 67" maxlength="20" wire:model.blur="phone" aria-describedby="co-phone-hint" @if ($errors->has('phone')) aria-invalid="true" @endif></div>
                        <span class="bz-hint" id="co-phone-hint">{{ $errors->first('phone') ?: 'Надішлемо SMS з номером ТТН' }}</span>
                    </div>
                    <div @class(['bz-field', 'bz-field--error' => $errors->has('name')])>
                        <label class="bz-label" for="co-name">Ім’я та прізвище отримувача</label>
                        <input class="bz-input" id="co-name" type="text" autocomplete="name" placeholder="Олена Коваль" wire:model.blur="name" @if ($errors->has('name')) aria-invalid="true" aria-describedby="co-name-err" @endif>
                        @error('name')<span class="bz-hint" id="co-name-err">{{ $message }}</span>@enderror
                    </div>
                </div>
            </fieldset>

            {{-- 2. Доставка Новою Поштою --}}
            <fieldset class="gap-3 border-0 p-0 md:gap-4 md:rounded-(--radius-xl) md:border md:p-7" style="{{ $fieldset }}">
                <legend class="md:sr-only" style="padding: 0; margin-bottom: 16px; font-size: 18px; line-height: 24px; font-weight: 800">2. Доставка Новою Поштою</legend>
                <div class="only-desk" style="display: flex; align-items: center; gap: 12px; font-size: 20px; line-height: 26px; font-weight: 800" aria-hidden="true"><span style="{{ $stepNo }}">2</span>Доставка Новою Поштою</div>

                {{-- TODO(НП API): автодоповнення міста --}}
                <div @class(['bz-field', 'bz-field--error' => $errors->has('city')])>
                    <label class="bz-label" for="co-city">Місто</label>
                    <input class="bz-input" id="co-city" type="text" autocomplete="address-level2" placeholder="Київ" wire:model.blur="city" @if ($errors->has('city')) aria-invalid="true" @endif>
                    @error('city')<span class="bz-hint">{{ $message }}</span>@enderror
                </div>

                <div class="grid gap-3 md:grid-cols-3" role="radiogroup" aria-label="Спосіб доставки">
                    @foreach ($deliveryOptions as $value => $option)
                        <label wire:key="dl-{{ $value }}" @class(['opt', 'on' => $delivery === $value]) style="padding: 14px 16px">
                            <input class="sr" type="radio" name="delivery" value="{{ $value }}" wire:model.live="delivery">
                            <span class="dot"></span>
                            <span style="flex: 1; min-width: 0">
                                <b>{{ $option['title'] }}</b>
                                <small class="only-mob">{{ $option['mob'] }}</small>
                                <small class="only-desk">{{ $option['desk'] }} · {{ $deliveryValue($option['cost']) }}</small>
                            </span>
                            <span class="only-mob" style="font-size: 14px; font-weight: 800; white-space: nowrap">{{ $deliveryValue($option['cost']) }}</span>
                        </label>
                    @endforeach
                </div>

                @if ($courier)
                    <div class="grid grid-cols-[1fr_96px] gap-3 md:grid-cols-[1fr_120px_120px]">
                        <div @class(['bz-field col-span-2 md:col-span-1', 'bz-field--error' => $errors->has('street')])>
                            <label class="bz-label" for="co-street">Вулиця</label>
                            <input class="bz-input" id="co-street" type="text" autocomplete="address-line1" placeholder="вул. Хрещатик" wire:model.blur="street">
                            @error('street')<span class="bz-hint">{{ $message }}</span>@enderror
                        </div>
                        <div @class(['bz-field', 'bz-field--error' => $errors->has('building')])>
                            <label class="bz-label" for="co-building">Будинок</label>
                            <input class="bz-input" id="co-building" type="text" placeholder="12" wire:model.blur="building">
                            @error('building')<span class="bz-hint">{{ $message }}</span>@enderror
                        </div>
                        <div @class(['bz-field', 'bz-field--error' => $errors->has('apartment')])>
                            <label class="bz-label" for="co-apartment">Квартира</label>
                            <input class="bz-input" id="co-apartment" type="text" placeholder="—" wire:model.blur="apartment">
                            @error('apartment')<span class="bz-hint">{{ $message }}</span>@enderror
                        </div>
                    </div>
                @else
                    {{-- TODO(НП API): список відділень / поштоматів обраного міста з пошуком за номером або вулицею --}}
                    <div wire:key="wh-{{ $delivery }}" @class(['bz-field', 'bz-field--error' => $errors->has('warehouse')])>
                        <label class="bz-label" for="co-branch">{{ $locker ? 'Поштомат' : 'Відділення' }}</label>
                        <input class="bz-input" id="co-branch" type="text" placeholder="{{ $locker ? 'Поштомат № 5012, вул. [адреса]' : 'Відділення № 25, вул. [адреса]' }}" wire:model.blur="warehouse">
                        <span class="bz-hint">{{ $errors->first('warehouse') ?: 'Почніть вводити номер або вулицю' }}</span>
                    </div>
                @endif
            </fieldset>

            {{-- 3. Оплата --}}
            <fieldset class="gap-3 border-0 p-0 md:gap-4 md:rounded-(--radius-xl) md:border md:p-7" style="{{ $fieldset }}">
                <legend class="md:sr-only" style="padding: 0; margin-bottom: 16px; font-size: 18px; line-height: 24px; font-weight: 800">3. Оплата</legend>
                <div class="only-desk" style="display: flex; align-items: center; gap: 12px; font-size: 20px; line-height: 26px; font-weight: 800" aria-hidden="true"><span style="{{ $stepNo }}">3</span>Оплата</div>
                <div class="grid gap-3 md:grid-cols-3" role="radiogroup" aria-label="Спосіб оплати">
                    @foreach ($payments as $value => [$mobTitle, $mobHint, $deskTitle, $deskHint])
                        <label wire:key="pay-{{ $value }}" @class(['opt', 'on' => $payment === $value]) style="padding: 14px 16px">
                            <input class="sr" type="radio" name="payment" value="{{ $value }}" wire:model.live="payment">
                            <span class="dot"></span>
                            <span style="flex: 1; min-width: 0">
                                <b class="only-mob">{{ $mobTitle }}</b><small class="only-mob">{{ $mobHint }}</small>
                                <b class="only-desk">{{ $deskTitle }}</b><small class="only-desk">{{ $deskHint }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
                @if ($cod)
                    <span class="mut only-desk">Нова Пошта бере комісію за післяплату: {{ $codTerms }}.</span>
                @endif
            </fieldset>

            <div class="flex flex-col gap-3">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 600; cursor: pointer">
                    <input type="checkbox" wire:model.live="withComment" style="width: 18px; height: 18px; accent-color: #1f1f24">Додати коментар до замовлення
                </label>
                @if ($withComment)
                    <div @class(['bz-field', 'bz-field--error' => $errors->has('comment')])>
                        <label class="sr" for="co-comment">Коментар до замовлення</label>
                        <textarea class="bz-input" id="co-comment" rows="3" maxlength="1000" wire:model.blur="comment" style="height: auto; padding-top: 12px; padding-bottom: 12px; resize: vertical"></textarea>
                        @error('comment')<span class="bz-hint">{{ $message }}</span>@enderror
                    </div>
                @endif
            </div>
        </form>

        {{-- Підсумок: на телефоні — під формою, на десктопі — праворуч --}}
        <aside class="sum gap-2.5! px-4! py-5! md:gap-3! md:p-6! lg:sticky lg:top-6" aria-label="Ваше замовлення">
            <div class="only-desk" style="display: flex; justify-content: space-between; align-items: baseline">
                <h2 class="h3">Ваше замовлення</h2>
                <a href="{{ route('cart') }}" style="font-size: 14px; font-weight: 700">Змінити</a>
            </div>
            <div class="only-desk" style="display: flex; flex-direction: column; gap: 12px">
                @foreach ($lines as $line)
                    <div wire:key="sum-{{ $line['variant']->id }}" style="display: flex; gap: 12px; align-items: center">
                        <div class="img" style="width: 56px; height: 56px; border-radius: 10px; flex: none">
                            @if ($line['product']->mainImage)<img src="{{ $line['product']->mainImage->url }}" alt="">@endif
                        </div>
                        <span style="flex: 1; font-size: 14px; line-height: 19px; font-weight: 700">{{ $line['product']->name }}<span class="mut" style="display: block">{{ $line['variant']->label ? $line['variant']->label.' · ' : '' }}{{ $line['quantity'] }} шт</span></span>
                        <b class="tabular" style="font-size: 15px">{{ money($line['total']) }}</b>
                    </div>
                @endforeach
            </div>
            <div class="hr only-desk"></div>

            <div class="row"><span>Товари ({{ $count }})</span><span class="tabular">{{ money($subtotal) }}</span></div>
            @if ($bundleDiscount > 0)
                <div class="row" style="font-weight: 700; color: var(--sale)"><span>Разом дешевше</span><span class="tabular">−{{ money($bundleDiscount) }}</span></div>
            @endif
            <div class="row"><span>Доставка</span><span>{{ $deliveryCost === null ? 'Безкоштовно' : '≈ '.money($deliveryCost).' при отриманні' }}</span></div>
            @if ($cod)
                <div class="row"><span>Комісія за післяплату</span><span class="tabular">≈ {{ money($codFee) }}</span></div>
            @endif
            <div class="hr"></div>
            <div style="display: flex; justify-content: space-between; align-items: baseline">
                <span style="font-size: 18px; font-weight: 800">До сплати</span>
                <span class="tabular text-[28px] leading-8 md:text-[32px] md:leading-9" style="font-weight: 800">{{ money($total) }}</span>
            </div>
            @if ($cod)
                <span class="mut only-mob">Платите у відділенні після огляду. Нова Пошта бере комісію {{ $codTerms }}.</span>
                <span class="mut only-desk">Платите у відділенні після огляду.</span>
            @elseif ($deliveryCost !== null)
                <span class="mut">Доставку оплачуєте у відділенні за тарифом Нової Пошти.</span>
            @endif

            <button class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" type="submit" form="checkout" wire:loading.attr="disabled" wire:target="place">{{ $confirmLabel }}</button>
            <span style="font-size: 12px; line-height: 17px; font-weight: 600; color: var(--ink-muted); text-align: center">Натискаючи кнопку, ви погоджуєтесь з <a href="{{ link_to_route_or_hash('info.offer') }}">публічною офертою</a> і <a href="{{ link_to_route_or_hash('info.privacy') }}">політикою конфіденційності</a>.</span>
        </aside>
    </div>
</div>
