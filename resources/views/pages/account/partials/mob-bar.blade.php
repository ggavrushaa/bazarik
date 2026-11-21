{{-- Мобільна смужка «назад + заголовок» під шапкою (AuthPhone, AccountOrders, AccountOrder, StateTracking…). Параметри: $back, $title --}}
<div class="bz-header__bar only-mob" style="border-bottom: 1px solid var(--line)">
    <a class="bz-icon-btn" href="{{ $back }}" aria-label="Назад"><x-bz.icon name="back" /></a>
    <h1 style="margin: 0 auto; font-size: 18px; line-height: 24px; font-weight: 800">{{ $title ?? '' }}</h1>
    <span style="width: 44px" aria-hidden="true"></span>
</div>
