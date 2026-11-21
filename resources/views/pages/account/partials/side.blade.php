{{-- Десктопне бокове меню кабінету (DeskAccount / DeskAddresses). Параметри: $customer, $active (orders | favorites | addresses) --}}
<div style="display: flex; flex-direction: column; gap: 20px">
    <div style="display: flex; flex-direction: column; gap: 2px; padding: 0 14px">
        <b style="font-size: 20px; font-weight: 800">{{ $customer->name }}</b>
        <span class="mut">{{ \App\Support\Phone::format($customer->phone) }}</span>
    </div>
    <nav class="side" aria-label="Кабінет" style="display: flex; flex-direction: column; gap: 2px">
        <a href="{{ route('account.orders') }}" @class(['on' => $active === 'orders']) @if ($active === 'orders') aria-current="page" @endif><x-bz.icon name="package" size="20" />Мої замовлення</a>
        <a href="{{ route('account.favorites') }}" @class(['on' => $active === 'favorites']) @if ($active === 'favorites') aria-current="page" @endif><x-bz.icon name="heart" size="20" />Обране</a>
        <a href="{{ route('account.addresses') }}" @class(['on' => $active === 'addresses']) @if ($active === 'addresses') aria-current="page" @endif><x-bz.icon name="pin" size="20" />Адреси доставки</a>
        <a href="{{ link_to_route_or_hash('account.notifications') }}"><x-bz.icon name="bell" size="20" />Сповіщення</a>
        <a href="{{ link_to_route_or_hash('info.delivery') }}"><x-bz.icon name="help" size="20" />Допомога</a>
        @include('pages.account.partials.logout', ['side' => true])
    </nav>
</div>
