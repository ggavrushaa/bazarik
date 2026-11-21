{{-- Форма «Повідомити мене» (StateOutOfStock / DeskOutOfStock): телефон → підтвердження --}}
<div>
    @if ($saved)
        <div class="pp-notify-done" role="status">
            <x-bz.icon name="check" size="22" style="color: var(--success); flex: none" />
            <span>Готово! Надішлемо SMS на <b class="tabular" style="white-space: nowrap">{{ $saved }}</b>, щойно товар з’явиться.</span>
        </div>
    @else
        <form class="pp-notify-form" wire:submit="save" novalidate>
            <div @class(['bz-field', 'pp-notify-field', 'bz-field--error' => $errors->has('phone')])>
                <label class="bz-label pp-notify-label" for="notify-phone">Телефон</label>
                <div class="bz-input-wrap" style="background: var(--bg)">
                    <span class="bz-prefix" aria-hidden="true">+380</span>
                    <input id="notify-phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="67 123 45 67"
                           wire:model="phone" maxlength="20" @error('phone') aria-invalid="true" aria-describedby="notify-error" @enderror>
                </div>
                @error('phone')<span class="bz-hint" id="notify-error">{{ $message }}</span>@enderror
            </div>
            <button class="bz-btn bz-btn--dark bz-btn--l" type="submit" wire:loading.attr="disabled">Повідомити мене</button>
        </form>
    @endif
</div>
