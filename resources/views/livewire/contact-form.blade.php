{{-- «Напишіть нам» (MobileContacts / DeskContacts): до 768px — поля одне під одним, від 768px — картка з рамкою --}}
<form wire:submit="send" novalidate
      class="flex flex-col gap-5 md:gap-4 md:rounded-(--radius-xl) md:border md:border-line md:p-7">
    <h2 class="only-mob" style="margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 20px; line-height: 26px">Напишіть нам</h2>
    <h2 class="h3 only-desk">Напишіть нам</h2>

    @if ($sent)
        <div role="status" style="display: flex; gap: 12px; align-items: flex-start; padding: 16px; border-radius: var(--radius-m); background: var(--success-soft); color: var(--success-strong); font-size: 15px; line-height: 22px; font-weight: 600">
            <x-bz.icon name="check" size="22" style="flex: none; color: var(--success)" />
            <span><b style="display: block; font-weight: 800">Дякуємо, повідомлення надіслано</b>Відповімо у робочий час: {{ config('shop.hours_short') }}.</span>
        </div>
    @else
        <div class="flex flex-col gap-5 md:grid md:grid-cols-2 md:gap-3">
            <div @class(['bz-field', 'bz-field--error' => $errors->has('name')])>
                <label class="bz-label" for="{{ $prefix }}-name">Ім’я</label>
                <input class="bz-input" id="{{ $prefix }}-name" type="text" autocomplete="name" maxlength="100" wire:model="name"
                       @error('name') aria-invalid="true" aria-describedby="{{ $prefix }}-name-error" @enderror>
                @error('name')<span class="bz-hint" id="{{ $prefix }}-name-error">{{ $message }}</span>@enderror
            </div>
            <div @class(['bz-field', 'bz-field--error' => $errors->has('phone')])>
                <label class="bz-label" for="{{ $prefix }}-phone">Телефон</label>
                <div class="bz-input-wrap">
                    <span class="bz-prefix" aria-hidden="true">+380</span>
                    <input id="{{ $prefix }}-phone" type="tel" inputmode="tel" autocomplete="tel-national" maxlength="20" wire:model="phone"
                           @error('phone') aria-invalid="true" aria-describedby="{{ $prefix }}-phone-error" @enderror>
                </div>
                @error('phone')<span class="bz-hint" id="{{ $prefix }}-phone-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div @class(['bz-field', 'bz-field--error' => $errors->has('message')])>
            <label class="bz-label" for="{{ $prefix }}-message">Повідомлення</label>
            <textarea id="{{ $prefix }}-message" class="bz-input" rows="4" maxlength="2000" wire:model="message"
                      style="height: auto; padding: 12px 16px; font: 500 16px/1.4 var(--font-sans); resize: vertical"
                      @error('message') aria-invalid="true" aria-describedby="{{ $prefix }}-message-error" @enderror></textarea>
            @error('message')<span class="bz-hint" id="{{ $prefix }}-message-error">{{ $message }}</span>@enderror
        </div>
        {{-- Пастка для ботів --}}
        <div aria-hidden="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden">
            <label for="{{ $prefix }}-website">Сайт</label>
            <input id="{{ $prefix }}-website" type="text" tabindex="-1" autocomplete="off" wire:model="website">
        </div>
        <button class="bz-btn bz-btn--dark bz-btn--l bz-btn--block only-mob" type="submit" wire:loading.attr="disabled" wire:target="send">Надіслати</button>
        <button class="bz-btn bz-btn--secondary bz-btn--l only-desk" type="submit" wire:loading.attr="disabled" wire:target="send">Надіслати</button>
    @endif
</form>
