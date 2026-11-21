{{--
    Поле коду з SMS: одне справжнє поле (автопідстановка one-time-code), поверх нього — клітинки з макета.
    Щойно введено всі цифри — входимо, передаючи обране гостя з localStorage для злиття з кабінетом.
    Таймер «Надіслати ще раз через 0:42» рахує в Alpine від секунд із сервера.
    Параметри: $id, $desk (bool).
--}}
@php
    $len = (int) config('shop.otp.length');
    $left = $this->resendIn();
    $box = $desk
        ? 'width: 56px; height: 64px; border-radius: var(--radius-m); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800'
        : 'width: 56px; height: 64px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font: 900 28px/1 var(--font-display)';
    $error = $errors->first('code');
@endphp
<div style="display: flex; flex-direction: column; gap: {{ $desk ? '20px' : '18px' }}"
     x-data="{
        len: {{ $len }},
        left: {{ $left }},
        timer: null,
        get clock() { return Math.floor(this.left / 60) + ':' + String(this.left % 60).padStart(2, '0') },
        tick() { clearInterval(this.timer); this.timer = setInterval(() => { if (this.left > 0) this.left-- }, 1000) },
        go() { $wire.verify(Alpine.store('favorites')?.ids ?? []) },
        init() { this.tick(); this.$nextTick(() => this.$refs.otp?.focus()) },
     }"
     x-on:code-resent.window="left = $event.detail.seconds; tick(); $refs.otp.focus()">
    <div>
        <label class="sr" for="otp-{{ $id }}">Код з SMS</label>
        <div style="position: relative; display: flex; gap: 10px; {{ $desk ? '' : 'justify-content: center; margin: 8px 0' }}" @if ($desk) role="group" aria-label="Код із {{ $len }} цифр" @endif>
            <input id="otp-{{ $id }}" x-ref="otp" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="{{ $len }}"
                   wire:model="code"
                   x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, len); $wire.code = $el.value; if ($el.value.length === len) go()"
                   @if ($error) aria-invalid="true" aria-describedby="otp-{{ $id }}-err" @endif
                   style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; border: 0; font-size: 16px; caret-color: transparent; cursor: text">
            @for ($i = 0; $i < $len; $i++)
                <span aria-hidden="true"
                      style="{{ $box }}; border: 1.5px solid {{ $error ? 'var(--sale)' : 'var(--line-strong)' }}"
                      :style="{ border: (($wire.code || '').length === {{ $i }} ? '2px solid var(--ink)' : '1.5px solid {{ $error ? 'var(--sale)' : 'var(--line-strong)' }}') }"
                      x-text="($wire.code || '')[{{ $i }}] ?? ''"></span>
            @endfor
        </div>
        @if ($error)
            <p id="otp-{{ $id }}-err" role="alert" style="margin: 8px 0 0; {{ $desk ? '' : 'text-align: center;' }} font-size: 14px; font-weight: 700; color: var(--sale)">{{ $error }}</p>
        @endif
    </div>

    <span @class(['mut' => $desk]) @if (! $desk) style="text-align: center; font-size: 14px; font-weight: 600; color: var(--ink-muted)" @endif x-show="left > 0">
        Надіслати ще раз через <span x-text="clock">{{ \App\Actions\Auth\SendLoginCode::clock($left) }}</span>
    </span>
    <button type="button" x-cloak x-show="left === 0" wire:click="resend" wire:loading.attr="disabled"
            @class(['bz-btn bz-btn--ghost', 'bz-btn--block' => ! $desk]) @if ($desk) style="align-self: flex-start; padding: 0" @endif>
        Надіслати ще раз
    </button>

    <button type="button" x-on:click="go()" wire:loading.attr="disabled" wire:target="verify"
            @class(['bz-btn bz-btn--l bz-btn--block', 'bz-btn--dark' => $desk, 'bz-btn--primary' => ! $desk]) @if (! $desk) style="margin-top: 8px" @endif>
        Увійти
    </button>
</div>
