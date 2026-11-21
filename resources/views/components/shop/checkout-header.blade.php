{{-- Скорочена шапка оформлення: без пошуку й меню, щоб не відволікати (MobileCheckout / DeskCheckout) --}}
@props(['title' => 'Оформлення', 'back' => null])
<header class="bz-header only-mob">
    <div class="bz-header__bar">
        <a class="bz-icon-btn" href="{{ $back ?? route('cart') }}" aria-label="Назад до кошика"><x-bz.icon name="back" /></a>
        <h1 style="margin: 0 auto; font-size: 18px; line-height: 24px; font-weight: 800">{{ $title }}</h1>
        <span style="width: 44px" aria-hidden="true"></span>
    </div>
</header>
<header class="only-desk" style="border-bottom: 1px solid var(--line)">
    <div class="wrap dk-bar" style="justify-content: space-between">
        <a class="bz-logo" href="{{ route('home') }}" aria-label="Bazarik, на головну"><x-logo height="44" /></a>
        <span style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700"><x-bz.icon name="lock" size="20" />Оформлення замовлення</span>
        <span style="display: flex; flex-direction: column; align-items: flex-end; font-size: 15px; font-weight: 800">{{ config('shop.phone') }}<span class="mut">{{ config('shop.hours_short') }}</span></span>
    </div>
</header>
