{{-- Модалка входу (DeskAuth / DeskAuthCode / DeskAuthName); для покупця, що увійшов, — лише синхронізація сердечок --}}
<div>
    @if ($open)
        @php
            $perk = 'display: flex; gap: 10px; align-items: center; font-size: 14px; font-weight: 700';
        @endphp
        <div x-data x-on:keydown.escape.window="$wire.close()">
            <div class="scrim" wire:click="close"></div>
            <div class="bz-modal" role="dialog" aria-modal="true" aria-labelledby="auth-title"
                 style="width: min(480px, calc(100vw - 32px)); padding: 32px; display: flex; flex-direction: column; gap: 20px">
                <div style="display: flex; justify-content: space-between; align-items: flex-start">
                    <h2 class="h1" id="auth-title" style="font-size: 28px; line-height: 34px">
                        @switch($step)
                            @case('code') Введіть код @break
                            @case('name') Як до вас звертатися? @break
                            @default Вхід або реєстрація
                        @endswitch
                    </h2>
                    <button class="bz-icon-btn" type="button" wire:click="close" aria-label="Закрити" style="margin: -8px -8px 0 0"><x-bz.icon name="close" size="22" /></button>
                </div>

                @if ($step === 'phone')
                    <form wire:submit="sendCode" style="display: flex; flex-direction: column; gap: 20px; margin-top: -8px">
                        <p class="p">Введіть номер — надішлемо код у SMS або Viber. Пароль не потрібен.</p>
                        <div @class(['bz-field', 'bz-field--error' => $errors->has('phone')])>
                            <label class="bz-label" for="auth-ph">Телефон</label>
                            <div class="bz-input-wrap"><span class="bz-prefix" aria-hidden="true">+380</span><input id="auth-ph" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="67 123 45 67" maxlength="20" wire:model="phone" x-init="$nextTick(() => $el.focus())" @if ($errors->has('phone')) aria-invalid="true" aria-describedby="auth-ph-err" @endif></div>
                            @error('phone')<span class="bz-hint" id="auth-ph-err" role="alert">{{ $message }}</span>@enderror
                        </div>
                        <button class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" type="submit" wire:loading.attr="disabled">Отримати код</button>
                        <ul style="margin: 0; padding: 16px; list-style: none; display: flex; flex-direction: column; gap: 10px; border-radius: var(--radius-m); background: var(--surface)">
                            <li style="{{ $perk }}"><x-bz.icon name="package" size="20" />Історія замовлень і статуси</li>
                            <li style="{{ $perk }}"><x-bz.icon name="heart" size="20" />Обране на всіх пристроях</li>
                            <li style="{{ $perk }}"><x-bz.icon name="bolt" size="20" />Покупка в 1 клік без дзвінка менеджера</li>
                        </ul>
                        <span class="mut" style="text-align: center">Продовжуючи, ви погоджуєтесь з <a href="{{ link_to_route_or_hash('info.offer') }}">офертою</a> і <a href="{{ link_to_route_or_hash('info.privacy') }}">політикою конфіденційності</a></span>
                    </form>
                @elseif ($step === 'code')
                    <p class="p" style="margin-top: -8px">Надіслали на {{ $this->sentToLabel() }} · <a href="#" wire:click.prevent="changeNumber">змінити</a></p>
                    @include('livewire.auth-code-input', ['id' => 'm', 'desk' => true])
                    <span class="mut">Вперше у нас? Після коду спитаємо лише ім’я.</span>
                @else
                    <form wire:submit="saveName" style="display: flex; flex-direction: column; gap: 20px; margin-top: -8px">
                        <p class="p">Це один раз — далі будемо підставляти в замовлення.</p>
                        <div @class(['bz-field', 'bz-field--error' => $errors->has('fullName')])>
                            <label class="bz-label" for="auth-fn">Ім’я та прізвище</label>
                            <input class="bz-input" id="auth-fn" type="text" autocomplete="name" placeholder="Олена Коваль" maxlength="100" wire:model="fullName" x-init="$nextTick(() => $el.focus())" @if ($errors->has('fullName')) aria-invalid="true" aria-describedby="auth-fn-err" @endif>
                            @error('fullName')<span class="bz-hint" id="auth-fn-err" role="alert">{{ $message }}</span>@enderror
                        </div>
                        <label style="display: flex; gap: 10px; align-items: flex-start; font-size: 14px; line-height: 20px; font-weight: 600; cursor: pointer"><input type="checkbox" wire:model="viber" style="width: 18px; height: 18px; margin: 1px 0 0; accent-color: var(--ink); flex: none">Надсилати знижки й новинки у Viber — не частіше раз на тиждень</label>
                        @if ($mergeNotice)
                            <div style="display: flex; gap: 10px; align-items: center; padding: 14px 16px; border-radius: var(--radius-m); background: var(--success-soft); color: var(--success-strong); font-size: 14px; line-height: 20px; font-weight: 700"><x-bz.icon name="check" size="20" stroke="2.4" />{{ $mergeNotice }}</div>
                        @endif
                        <button class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" type="submit" wire:loading.attr="disabled">Готово</button>
                    </form>
                @endif
            </div>
        </div>
    @elseif ($customer)
        {{--
            Обране покупця: на пристрої (localStorage, $store.favorites) і в кабінеті (БД).
            Перший раз на пристрої — об'єднуємо; далі кабінет головний, а зміни сердечок ($watch) пишемо в БД.
        --}}
        <div x-data="{
                last: [],
                save(ids) {
                    this.last = ids;
                    Alpine.store('favorites').ids = ids;
                    try { localStorage.setItem('bz:favorites', JSON.stringify(ids)); localStorage.setItem('bz:favorites-owner', '{{ $customer->id }}'); } catch (e) {}
                },
                init() {
                    const store = Alpine.store('favorites');
                    if (! store) return;
                    const db = @js($favoriteIds);
                    let owner = null;
                    try { owner = localStorage.getItem('bz:favorites-owner'); } catch (e) {}
                    if (owner === '{{ $customer->id }}') {
                        this.save(db);
                    } else {
                        const added = store.ids.filter((id) => ! db.includes(id));
                        this.save([...new Set([...db, ...store.ids])]);
                        if (added.length) $wire.syncFavorites(added, []);
                    }
                    this.$watch('$store.favorites.ids', (ids) => {
                        const added = ids.filter((id) => ! this.last.includes(id));
                        const removed = this.last.filter((id) => ! ids.includes(id));
                        this.last = [...ids];
                        if (added.length || removed.length) $wire.syncFavorites(added, removed);
                    });
                },
             }"></div>
    @endif
</div>
