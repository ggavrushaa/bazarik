{{-- Відстеження за посиланням з SMS: StateTracking (телефон) / DeskTracking (десктоп). Доступ — за public_token, без входу. --}}
@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
    use App\Http\Controllers\AccountController;
    use App\Http\Controllers\TrackingController;

    $steps = TrackingController::timeline($order, place: true);
    $deskSteps = TrackingController::timeline($order, transit: true);
    $place = AccountController::place($order);
    $badge = AccountController::badge($order->status);
    $expected = TrackingController::expectedAt($order);
    $own = auth('customer')->id() && auth('customer')->id() === $order->customer_id;
    $npUrl = $order->ttn ? 'https://novaposhta.ua/tracking/?cargo_number='.urlencode($order->ttn) : null;
    $toPay = $order->payment_method === PaymentMethod::CashOnDelivery && $order->payment_status !== PaymentStatus::Paid;
    $lc = fn (string $s) => mb_strtolower(mb_substr($s, 0, 1)).mb_substr($s, 1);

    $headline = match ($order->status) {
        OrderStatus::Arrived => 'Чекає у відділенні',
        OrderStatus::Completed => 'Отримано',
        OrderStatus::Cancelled, OrderStatus::Returned => $order->status->getLabel(),
        default => 'Буде у відділенні '.delivery_label($expected, lower: true),
    };
    $boxNote = collect(['Нова Пошта, '.$lc($place), $order->ttn ? 'ТТН '.$order->ttn : null])->filter()->implode(' · ');
@endphp
<x-layouts.app :title="'Замовлення № '.$order->number">
    @include('pages.account.partials.mob-bar', ['back' => $own ? route('account.order', $order) : route('home'), 'title' => 'Де моє замовлення'])

    {{-- ── Телефон ── --}}
    <div class="only-mob" style="display: flex; flex-direction: column; gap: 16px; padding: 20px 16px 32px">
        <section style="display: flex; flex-direction: column; gap: 14px; padding: 16px; border-radius: 16px; border: 1px solid var(--line)">
            <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 12px">
                <h1 style="margin: 0; font-size: 17px; font-weight: 800">№ {{ $order->number }}</h1>
                <span class="{{ $badge }}">{{ $order->status->getLabel() }}</span>
            </div>
            @include('pages.account.partials.timeline', ['steps' => $steps])
            @if ($place)
                <div style="display: flex; gap: 8px; align-items: flex-start; font-size: 14px; line-height: 20px; color: var(--ink-2); font-weight: 600"><x-bz.icon name="pin" size="20" />{{ $place }}</div>
            @endif
            @if ($npUrl)
                <a href="{{ $npUrl }}" target="_blank" rel="noopener" style="font-size: 14px; font-weight: 700">Відкрити в Новій Пошті</a>
            @endif
        </section>
        <a class="bz-btn bz-btn--ghost bz-btn--block" href="{{ route('tracking') }}">Знайти інше замовлення</a>
    </div>

    {{-- ── Десктоп ── --}}
    <div class="wrap only-desk" style="padding-top: 32px">
        <nav class="crumbs" aria-label="Навігація">
            @if ($own)
                <a href="{{ route('account') }}">Кабінет</a>
            @else
                <a href="{{ route('home') }}">Головна</a>
            @endif
            <span>/</span><span>Замовлення № {{ $order->number }}</span>
        </nav>
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 48px; align-items: start; margin-top: 8px; padding-bottom: 72px">
            <div style="display: flex; flex-direction: column; gap: 24px">
                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap">
                    <h1 class="h1" style="font-size: 36px; line-height: 42px">Замовлення № {{ $order->number }}</h1>
                    <span class="{{ $badge }}">{{ $order->status->getLabel() }}</span>
                </div>
                <div class="bz-delivery bz-delivery--box"><x-bz.icon name="truck" /><span>{{ $headline }}<small>{{ $boxNote }}</small></span></div>
                @include('pages.account.partials.timeline', ['steps' => $deskSteps, 'desk' => true])
                @if ($npUrl)
                    <a href="{{ $npUrl }}" target="_blank" rel="noopener" style="font-size: 15px; font-weight: 700">Відкрити в Новій Пошті</a>
                @endif
            </div>

            <aside class="sum">
                <h2 class="h3">У замовленні</h2>
                @foreach ($order->items as $item)
                    <div style="display: flex; gap: 12px; align-items: center">
                        <div class="img" style="width: 56px; height: 56px; border-radius: 10px; flex: none">
                            @if ($item->product?->mainImage)<img src="{{ $item->product->mainImage->url }}" alt="">@endif
                        </div>
                        <span style="flex: 1; font-size: 14px; line-height: 19px; font-weight: 700">{{ $item->name }}<span class="mut" style="display: block">{{ $item->variant_label ? $item->variant_label.' · ' : '' }}{{ $item->quantity }} шт</span></span>
                        <b class="tabular" style="font-size: 15px; white-space: nowrap">{{ money($item->total) }}</b>
                    </div>
                @endforeach
                <div class="hr"></div>
                <div class="row"><span>{{ $toPay ? 'До сплати при отриманні' : 'Разом' }}</span><b class="tabular" style="color: var(--ink)">{{ money($order->total) }}</b></div>
                <a class="bz-btn bz-btn--secondary bz-btn--block" href="{{ config('shop.viber') }}">Написати у Viber</a>
                <a href="{{ link_to_route_or_hash('info.delivery') }}" style="text-align: center; font-size: 14px; font-weight: 700">Змінити відділення</a>
            </aside>
        </div>
    </div>
</x-layouts.app>
