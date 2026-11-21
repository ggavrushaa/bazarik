{{-- Адреси доставки: AccountAddresses (телефон) / DeskAddresses (десктоп); форма — шторка знизу на телефоні, модалка на десктопі --}}
@php
    use App\Enums\DeliveryMethod;
    use App\Http\Controllers\AccountController;
    use App\Support\Phone;

    $methods = [
        DeliveryMethod::NovaPoshtaWarehouse->value => 'Відділення',
        DeliveryMethod::NovaPoshtaLocker->value => 'Поштомат',
        DeliveryMethod::NovaPoshtaCourier->value => 'Кур’єр',
    ];
    $courier = $delivery === DeliveryMethod::NovaPoshtaCourier->value;
    $locker = $delivery === DeliveryMethod::NovaPoshtaLocker->value;
    $link = 'border: 0; padding: 0; background: none; font: 700 14px var(--font-sans); color: var(--link, var(--ink)); text-decoration: underline; cursor: pointer';
@endphp
<div style="display: flex; flex-direction: column; gap: 12px" class="md:gap-6!">
    <div class="flex flex-col gap-3 md:grid md:grid-cols-3 md:gap-5">
        @foreach ($addresses as $address)
            @php
                $sub = collect([$address->delivery_method === DeliveryMethod::NovaPoshtaCourier ? 'Кур’єр' : null, $address->recipient_name, Phone::format($address->recipient_phone)])->filter()->implode(' · ');
            @endphp
            <article wire:key="addr-{{ $address->id }}"
                     class="flex flex-row items-start gap-3 rounded-2xl p-4 md:flex-col md:rounded-(--radius-l) md:p-6"
                     style="border: {{ $address->is_default ? '2px solid var(--ink)' : '1px solid var(--line)' }}">
                <span style="flex: 1; display: flex; flex-direction: column; gap: 2px" class="md:gap-3! md:order-2">
                    <b style="font-size: 15px; line-height: 20px" class="md:text-[17px]! md:leading-[22px]!">{{ AccountController::place($address) }}</b>
                    <span style="font-size: 13px; color: var(--ink-muted); font-weight: 600">{{ $sub }}</span>
                    @if ($address->is_default)
                        <span class="bz-badge bz-badge--stock only-mob" style="align-self: flex-start; margin-top: 6px">Основна</span>
                    @endif
                </span>
                <div class="only-desk md:order-1" style="min-height: 26px">
                    @if ($address->is_default)
                        <span style="padding: 4px 10px; border-radius: 999px; background: var(--surface-warm); font-size: 13px; font-weight: 800">Основна</span>
                    @endif
                </div>
                <span class="flex flex-col items-end gap-2 md:order-3 md:mt-auto md:flex-row md:items-center md:gap-4">
                    <button type="button" wire:click="edit({{ $address->id }})" style="{{ $link }}">Змінити</button>
                    @unless ($address->is_default)
                        <button type="button" wire:click="makePrimary({{ $address->id }})" style="{{ $link }}; color: var(--ink-muted)">Зробити основною</button>
                    @endunless
                </span>
            </article>
        @endforeach

        {{-- Десктоп: плитка «Додати адресу» --}}
        <button type="button" wire:click="create" class="only-desk"
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; min-height: 180px; border-radius: var(--radius-l); border: 1.5px dashed var(--line-strong); background: none; color: var(--ink); font: 800 16px var(--font-sans); cursor: pointer">
            <x-bz.icon name="plus" size="28" />Додати адресу
        </button>
    </div>

    <div class="only-mob" style="display: flex; flex-direction: column">
        <button class="bz-btn bz-btn--secondary bz-btn--l bz-btn--block" type="button" wire:click="create" style="margin-top: 4px">Додати адресу</button>
    </div>
    <p style="margin: 0; font-size: 13px; line-height: 19px; font-weight: 500; color: var(--ink-muted)" class="md:text-sm!">Основна адреса й спосіб оплати підставляються в «Купити в 1 клік».</p>

    @if ($editing !== null)
        <div x-data x-on:keydown.escape.window="$wire.cancel()">
            <div class="scrim" wire:click="cancel"></div>
            <form wire:submit="save" role="dialog" aria-modal="true" aria-labelledby="addr-title"
                  class="fixed inset-x-0 bottom-0 z-60 flex max-h-[92dvh] flex-col gap-4 overflow-y-auto rounded-t-(--radius-xl) px-4 pt-5 pb-6 md:inset-auto md:top-1/2 md:left-1/2 md:w-[560px] md:max-w-[calc(100vw-48px)] md:-translate-x-1/2 md:-translate-y-1/2 md:rounded-(--radius-xl) md:p-8"
                  style="background: var(--bg); box-shadow: var(--shadow-pop); box-sizing: border-box">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px">
                    <h2 id="addr-title" style="margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 22px; line-height: 28px">{{ $editing ? 'Змінити адресу' : 'Нова адреса' }}</h2>
                    <button class="bz-icon-btn" type="button" wire:click="cancel" aria-label="Закрити" style="margin: -8px -8px 0 0; background: transparent"><x-bz.icon name="close" size="22" /></button>
                </div>

                <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Спосіб доставки">
                    @foreach ($methods as $value => $label)
                        <label wire:key="am-{{ $value }}" @class(['opt', 'on' => $delivery === $value]) style="padding: 12px; justify-content: center">
                            <input class="sr" type="radio" name="addr-delivery" value="{{ $value }}" wire:model.live="delivery">
                            <b>{{ $label }}</b>
                        </label>
                    @endforeach
                </div>

                <div @class(['bz-field', 'bz-field--error' => $errors->has('city')])>
                    <label class="bz-label" for="addr-city">Місто</label>
                    <input class="bz-input" id="addr-city" type="text" autocomplete="address-level2" placeholder="Київ" wire:model="city">
                    @error('city')<span class="bz-hint">{{ $message }}</span>@enderror
                </div>

                @if ($courier)
                    <div class="grid grid-cols-[1fr_88px_88px] gap-3">
                        <div @class(['bz-field', 'bz-field--error' => $errors->has('street')])>
                            <label class="bz-label" for="addr-street">Вулиця</label>
                            <input class="bz-input" id="addr-street" type="text" autocomplete="address-line1" placeholder="вул. Хрещатик" wire:model="street">
                            @error('street')<span class="bz-hint">{{ $message }}</span>@enderror
                        </div>
                        <div @class(['bz-field', 'bz-field--error' => $errors->has('building')])>
                            <label class="bz-label" for="addr-building">Будинок</label>
                            <input class="bz-input" id="addr-building" type="text" placeholder="12" wire:model="building">
                            @error('building')<span class="bz-hint">{{ $message }}</span>@enderror
                        </div>
                        <div class="bz-field">
                            <label class="bz-label" for="addr-apartment">Квартира</label>
                            <input class="bz-input" id="addr-apartment" type="text" placeholder="—" wire:model="apartment">
                        </div>
                    </div>
                @else
                    <div wire:key="addr-wh-{{ $delivery }}" @class(['bz-field', 'bz-field--error' => $errors->has('warehouse')])>
                        <label class="bz-label" for="addr-wh">{{ $locker ? 'Поштомат' : 'Відділення' }}</label>
                        <input class="bz-input" id="addr-wh" type="text" placeholder="{{ $locker ? 'Поштомат № 5012, вул. [адреса]' : 'Відділення № 25, вул. [адреса]' }}" wire:model="warehouse">
                        @error('warehouse')<span class="bz-hint">{{ $message }}</span>@enderror
                    </div>
                @endif

                <div class="grid gap-3 md:grid-cols-2">
                    <div @class(['bz-field', 'bz-field--error' => $errors->has('recipientName')])>
                        <label class="bz-label" for="addr-name">Ім’я та прізвище отримувача</label>
                        <input class="bz-input" id="addr-name" type="text" autocomplete="name" placeholder="Олена Коваль" wire:model="recipientName">
                        @error('recipientName')<span class="bz-hint">{{ $message }}</span>@enderror
                    </div>
                    <div @class(['bz-field', 'bz-field--error' => $errors->has('recipientPhone')])>
                        <label class="bz-label" for="addr-phone">Телефон отримувача</label>
                        <div class="bz-input-wrap"><span class="bz-prefix" aria-hidden="true">+380</span><input id="addr-phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="67 123 45 67" maxlength="20" wire:model="recipientPhone"></div>
                        @error('recipientPhone')<span class="bz-hint">{{ $message }}</span>@enderror
                    </div>
                </div>

                <label style="display: flex; gap: 12px; align-items: center; font-size: 15px; font-weight: 700; cursor: pointer">
                    <input type="checkbox" wire:model="isDefault" style="width: 20px; height: 20px; accent-color: var(--ink); margin: 0">Основна адреса
                </label>

                <button class="bz-btn bz-btn--primary bz-btn--l bz-btn--block" type="submit" wire:loading.attr="disabled">Зберегти</button>
                @if ($editing)
                    <button class="bz-btn bz-btn--ghost bz-btn--block" type="button" wire:click="delete({{ $editing }})" wire:confirm="Видалити цю адресу?" style="color: var(--sale)">Видалити адресу</button>
                @endif
            </form>
        </div>
    @endif
</div>
