{{-- Вхід сторінками: AuthPhone / AuthCode / AuthName. На десктопі — та сама форма карткою по центру (зазвичай там модалка AuthModal). --}}
@php
    $back = match ($step) {
        'code' => route('login'),
        default => route('home'),
    };
    $h1 = 'margin: 0; font-family: var(--font-display); font-weight: 900; font-size: 28px; line-height: 34px';
    $lead = 'margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)';
    $perk = 'display: flex; gap: 10px; align-items: center; font-size: 15px; font-weight: 600; color: var(--ink-2)';
@endphp
<div>
    @include('pages.account.partials.mob-bar', ['back' => $back, 'title' => ''])

    <div class="md:mx-auto md:my-12 md:w-[480px] md:rounded-(--radius-xl) md:p-8 md:shadow-(--shadow-pop)">
        @if ($step === 'phone')
            <form wire:submit="sendCode" style="display: flex; flex-direction: column; gap: 18px" class="px-5 pt-4 pb-8 md:p-0">
                <h1 style="{{ $h1 }}">Вхід або реєстрація</h1>
                <p style="{{ $lead }}">Введіть номер — надішлемо код. Пароль не потрібен.</p>
                <div @class(['bz-field', 'bz-field--error' => $errors->has('phone')])>
                    <label class="bz-label" for="ph">Телефон</label>
                    <div class="bz-input-wrap"><span class="bz-prefix" aria-hidden="true">+380</span><input id="ph" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="67 123 45 67" maxlength="20" autofocus wire:model="phone" @if ($errors->has('phone')) aria-invalid="true" aria-describedby="ph-err" @endif></div>
                    @error('phone')<span class="bz-hint" id="ph-err" role="alert">{{ $message }}</span>@enderror
                </div>
                <button class="bz-btn bz-btn--primary bz-btn--l bz-btn--block" type="submit" wire:loading.attr="disabled">Отримати код</button>
                <ul style="margin: 8px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 12px">
                    <li style="{{ $perk }}"><x-bz.icon name="package" size="20" />Історія замовлень і статуси</li>
                    <li style="{{ $perk }}"><x-bz.icon name="heart" size="20" />Обране на всіх пристроях</li>
                    <li style="{{ $perk }}"><x-bz.icon name="bolt" size="20" />Покупка в 1 клік</li>
                </ul>
                <a class="bz-btn bz-btn--ghost bz-btn--block" href="{{ route('home') }}" style="margin-top: 8px">Продовжити без входу</a>
                <p style="margin: 0; font-size: 12px; line-height: 18px; color: var(--ink-muted); font-weight: 600; text-align: center">Продовжуючи, ви погоджуєтесь з <a href="{{ link_to_route_or_hash('info.offer') }}">офертою</a> і <a href="{{ link_to_route_or_hash('info.privacy') }}">політикою конфіденційності</a>.</p>
            </form>
        @elseif ($step === 'code')
            <div style="display: flex; flex-direction: column; gap: 18px" class="px-5 pt-4 pb-8 md:p-0">
                <h1 style="{{ $h1 }}">Введіть код</h1>
                <p style="{{ $lead }}">Надіслали SMS на {{ $this->sentToLabel() }}. <a href="{{ route('login') }}">Змінити номер</a></p>
                @include('livewire.auth-code-input', ['id' => 'p', 'desk' => false])
                <p style="margin: 0; font-size: 13px; line-height: 19px; color: var(--ink-muted); font-weight: 600; text-align: center">Код підставиться сам, щойно прийде SMS.</p>
            </div>
        @else
            <form wire:submit="saveName" style="display: flex; flex-direction: column; gap: 18px" class="px-5 pt-4 pb-8 md:p-0">
                <h1 style="{{ $h1 }}">Як до вас звертатися?</h1>
                <p style="{{ $lead }}">Це один раз — далі будемо підставляти в замовлення.</p>
                <div @class(['bz-field', 'bz-field--error' => $errors->has('fullName')])>
                    <label class="bz-label" for="fn">Ім’я та прізвище</label>
                    <input class="bz-input" id="fn" type="text" autocomplete="name" placeholder="Олена Коваль" maxlength="100" autofocus wire:model="fullName" @if ($errors->has('fullName')) aria-invalid="true" aria-describedby="fn-err" @endif>
                    @error('fullName')<span class="bz-hint" id="fn-err" role="alert">{{ $message }}</span>@enderror
                </div>
                <label style="display: flex; gap: 12px; align-items: flex-start; font-size: 14px; line-height: 20px; font-weight: 600; color: var(--ink-2); cursor: pointer"><input type="checkbox" wire:model="viber" style="width: 22px; height: 22px; accent-color: var(--ink); flex: none; margin: 0">Надсилати знижки й новинки у Viber — не частіше раз на тиждень</label>
                @if ($mergeNotice)
                    <div style="display: flex; gap: 10px; align-items: center; padding: 12px 14px; border-radius: 12px; background: var(--success-soft); color: var(--success-strong); font-size: 14px; font-weight: 700"><x-bz.icon name="check" size="20" stroke="2.4" />{{ $mergeNotice }}</div>
                @endif
                <button class="bz-btn bz-btn--primary bz-btn--l bz-btn--block" type="submit" wire:loading.attr="disabled">Готово</button>
            </form>
        @endif
    </div>
</div>
