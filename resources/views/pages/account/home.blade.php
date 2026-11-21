{{-- Кабінет: телефон — AccountHome (активне замовлення + розділи), десктоп — DeskAccount (бокове меню, замовлення, обране) --}}
@php
    use App\Http\Controllers\AccountController;
    use App\Http\Controllers\TrackingController;
    use App\Support\Phone;

    $greeting = $customer->first_name ? 'Вітаємо, '.AccountController::vocative($customer->first_name) : 'Вітаємо!';
    $row = 'display: flex; align-items: center; gap: 12px; min-height: 52px; padding: 0 4px; border-bottom: 1px solid var(--line); color: var(--ink); text-decoration: none; font-size: 16px; font-weight: 700';
    $count = 'min-width: 24px; height: 24px; padding: 0 7px; box-sizing: border-box; border-radius: 99px; background: var(--surface); font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center';
    $sections = [
        [route('account.orders'), 'package', 'Мої замовлення', null],
        [route('account.favorites'), 'heart', 'Обране', $favoritesCount],
        [route('account.addresses'), 'pin', 'Адреси доставки', $addressesCount],
        [link_to_route_or_hash('account.notifications'), 'bell', 'Сповіщення', null],
        [link_to_route_or_hash('account.language'), 'globe', 'Мова: українська', null],
        [link_to_route_or_hash('info.contacts'), 'help', 'Допомога', null],
    ];
@endphp
<x-layouts.app title="Кабінет">
    {{-- ── Телефон ── --}}
    <div class="only-mob" style="display: flex; flex-direction: column; gap: 18px; padding: 20px 16px 32px">
        <h1 style="margin: 0; font-family: var(--font-display); font-weight: 900; font-size: 28px; line-height: 34px">{{ $greeting }}</h1>
        <span style="font-size: 14px; font-weight: 600; color: var(--ink-muted); margin-top: -10px">{{ Phone::format($customer->phone) }}</span>

        @if ($activeOrder)
            @php
                $point = AccountController::place($activeOrder);
                $when = in_array($activeOrder->status, [\App\Enums\OrderStatus::Arrived], true)
                    ? 'Чекає у відділенні'
                    : delivery_label(TrackingController::expectedAt($activeOrder));
            @endphp
            <a href="{{ route('account.order', $activeOrder) }}" style="display: flex; flex-direction: column; gap: 10px; padding: 16px; border-radius: 16px; background: var(--surface); color: var(--ink); text-decoration: none">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px">
                    <b style="font-size: 15px">Замовлення № {{ $activeOrder->number }}</b>
                    <span class="{{ AccountController::badge($activeOrder->status) }}">{{ $activeOrder->status->getLabel() }}</span>
                </div>
                <span class="bz-delivery"><x-bz.icon name="truck" />{{ $when }}{{ $point ? ' · '.mb_strtolower(mb_substr($point, 0, 1)).mb_substr($point, 1) : '' }}</span>
                <div style="display: flex; gap: 6px">
                    @foreach ($activeOrder->items->map(fn ($i) => $i->product?->mainImage?->url)->filter()->take(4) as $url)
                        <span class="img" style="width: 44px; height: 44px; border-radius: 10px; background: #fff"><img src="{{ $url }}" alt=""></span>
                    @endforeach
                </div>
            </a>
        @endif

        <nav aria-label="Кабінет" style="display: flex; flex-direction: column">
            @foreach ($sections as [$href, $icon, $label, $n])
                <a href="{{ $href }}" style="{{ $row }}">
                    <x-bz.icon :name="$icon" size="22" />
                    <span style="flex: 1">{{ $label }}</span>
                    @if ($n)<span style="{{ $count }}">{{ $n }}</span>@endif
                    <x-bz.icon name="chevron-right" size="18" />
                </a>
            @endforeach
        </nav>
        @include('pages.account.partials.logout')
    </div>

    {{-- ── Десктоп ── --}}
    <div class="wrap only-desk" style="padding-top: 40px">
        <div style="display: grid; grid-template-columns: 264px 1fr; gap: 48px; padding-bottom: 72px">
            @include('pages.account.partials.side', ['active' => 'orders'])
            <div style="display: flex; flex-direction: column; gap: 24px; min-width: 0">
                <h1 class="h1" style="font-size: 36px; line-height: 42px">Мої замовлення</h1>
                <livewire:account-orders />

                @if ($favorites->isNotEmpty())
                    <div class="sechead" style="margin-top: 24px">
                        <h2 class="h2">Обране · {{ $favoritesCount }}</h2>
                        <a href="{{ route('account.favorites') }}">Усе обране →</a>
                    </div>
                    <div class="g4" style="grid-template-columns: repeat(4, minmax(0, 1fr))">
                        @foreach ($favorites as $product)
                            <x-shop.product-card :product="$product" :sizes="true" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
