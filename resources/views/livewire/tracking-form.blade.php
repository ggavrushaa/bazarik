{{-- Форма «Де моє замовлення» (StateTracking): номер замовлення + телефон --}}
<form wire:submit="find" style="display: flex; flex-direction: column; gap: 16px">
    <p style="margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">Введіть номер замовлення й телефон — покажемо, де посилка.</p>
    <div @class(['bz-field', 'bz-field--error' => $errors->has('number')])>
        <label class="bz-label" for="tr">№ замовлення</label>
        <input class="bz-input" id="tr" type="text" inputmode="text" autocomplete="off" placeholder="B-100001" maxlength="20" wire:model="number" @if ($errors->has('number')) aria-invalid="true" aria-describedby="tr-err" @endif>
        @error('number')<span class="bz-hint" id="tr-err" role="alert">{{ $message }}</span>@enderror
    </div>
    <div @class(['bz-field', 'bz-field--error' => $errors->has('phone')])>
        <label class="bz-label" for="tr-ph">Телефон</label>
        <div class="bz-input-wrap"><span class="bz-prefix" aria-hidden="true">+380</span><input id="tr-ph" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="67 123 45 67" maxlength="20" wire:model="phone" @if ($errors->has('phone')) aria-invalid="true" aria-describedby="tr-ph-err" @endif></div>
        @error('phone')<span class="bz-hint" id="tr-ph-err" role="alert">{{ $message }}</span>@enderror
    </div>
    <button class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" type="submit" wire:loading.attr="disabled">Знайти</button>
</form>
