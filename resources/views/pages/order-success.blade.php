{{-- «Дякуємо, замовлення прийнято»: StateSuccess (до 768px) / DeskSuccess (від 768px) --}}
@php
    use App\Enums\DeliveryMethod;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;

    $tz = config('app.timezone');
    $created = $order->created_at->copy()->tz($tz);
    $arrive = delivery_date($created);
    [$h, $m] = array_map('intval', explode(':', (string) config('shop.same_day_cutoff')));
    $shipsToday = $created->lt($created->copy()->setTime($h, $m)) && ! $created->isSunday() && $created->isToday();

    $digits = substr($order->phone, 4);
    $phone = '+380 '.substr($digits, 0, 2).' '.substr($digits, 2, 3).' '.substr($digits, 5, 2).' '.substr($digits, 7, 2);
    $recipient = trim($order->first_name.' '.$order->last_name);
    $cod = $order->payment_method === PaymentMethod::CashOnDelivery;

    // «Відділення № 25, вул. …» — як ввели; голий номер «25» → «відділення № 25»
    $point = (string) $order->warehouse;
    $lcfirst = fn (string $s) => mb_strtolower(mb_substr($s, 0, 1)).mb_substr($s, 1);
    [$deliveryText, $where, $place] = match ($order->delivery_method) {
        DeliveryMethod::NovaPoshtaLocker => [
            'Нова Пошта, '.(ctype_digit($point) ? 'поштомат № '.$point : $lcfirst($point)),
            'у поштоматі',
            (ctype_digit($point) ? 'Поштомат № '.$point : $point).', '.$order->city,
        ],
        DeliveryMethod::NovaPoshtaCourier => [
            'Нова Пошта, кур’єр',
            'привезе кур’єр',
            collect([$order->street, $order->building, $order->apartment ? 'кв. '.$order->apartment : null])->filter()->implode(', ').', '.$order->city,
        ],
        default => [
            'Нова Пошта, '.(ctype_digit($point) ? 'відділення № '.$point : $lcfirst($point)),
            'у відділенні',
            (ctype_digit($point) ? 'Відділення № '.$point : $point).', '.$order->city,
        ],
    };

    $payment = $cod ? 'При отриманні' : ($order->payment_status === PaymentStatus::Paid ? 'Онлайн, оплачено' : 'Онлайн · очікує оплати');
    $details = [
        ['Доставка', $deliveryText],
        ['Отримувач', $recipient],
        ['Оплата', $payment],
        ['До сплати', money($order->total)],
    ];
    $steps = [
        [($shipsToday ? 'Сьогодні' : 'Наступного робочого дня').' — SMS з номером ТТН', $shipsToday ? 'Відправляємо до вечора' : 'Відправимо зранку'],
        [delivery_label($arrive).' — '.$where, $place],
        $cod
            ? ['Оглядаєте й платите', money($order->total).' + комісія Нової Пошти']
            : ['Оглядаєте й забираєте', $order->payment_status === PaymentStatus::Paid ? 'Замовлення вже оплачене' : 'Посилання на оплату надішлемо в SMS'],
    ];
    $trackingUrl = link_to_route_or_hash('tracking');
@endphp
<x-layouts.app title="Замовлення прийнято">
    <div class="wrap flex flex-col gap-5 pt-12 pb-8 md:gap-10 md:pt-14 md:pb-[72px]">
        <section style="display: flex; flex-direction: column; align-items: center; gap: 16px; text-align: center">
            <svg width="148" height="120" viewBox="0 0 148 120" role="img" aria-label="Навіс над галочкою">
                <path d="M27.9 0.0H46.3L38.0 24.0A12.0 12.0 0 0 1 14.0 24.0Z" fill="#ff7a1a"></path>
                <path d="M46.3 0.0H64.8L62.0 24.0A12.0 12.0 0 0 1 38.0 24.0Z" fill="#d95e0a"></path>
                <path d="M64.8 0.0H83.2L86.0 24.0A12.0 12.0 0 0 1 62.0 24.0Z" fill="#ff7a1a"></path>
                <path d="M83.2 0.0H101.7L110.0 24.0A12.0 12.0 0 0 1 86.0 24.0Z" fill="#d95e0a"></path>
                <path d="M101.7 0.0H120.1L134.0 24.0A12.0 12.0 0 0 1 110.0 24.0Z" fill="#ff7a1a"></path>
                <circle cx="74" cy="78" r="34" fill="#e3f4ea"></circle>
                <path d="M58 78l11 11 21-23" fill="none" stroke="#14532d" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <div style="display: flex; flex-direction: column; gap: 6px">
                <h1 class="text-[28px] leading-[34px] md:text-[44px] md:leading-[50px]" style="margin: 0; font-family: var(--font-display); font-weight: 900">Дякуємо, замовлення прийнято</h1>
                <span class="text-[15px] font-bold md:text-lg md:font-medium" style="color: var(--ink-muted)">№ {{ $order->number }} · надіслали на {{ $phone }}</span>
            </div>
        </section>

        <section class="mx-auto grid w-full gap-5 md:max-w-[880px] md:grid-cols-2 md:gap-6">
            {{-- Деталі --}}
            <div class="flex flex-col gap-2.5 rounded-2xl p-4 md:gap-0 md:rounded-(--radius-xl) md:p-7" style="border: 1px solid var(--line)">
                <h2 class="h3 only-desk" style="margin-bottom: 8px">Деталі</h2>
                @foreach ($details as [$label, $value])
                    <div class="flex justify-between gap-3 text-sm leading-5 md:border-b md:py-3 md:text-[15px] md:leading-[22px]" style="border-color: var(--line)">
                        <span style="color: var(--ink-muted); font-weight: 600">{{ $label }}</span>
                        <b style="font-weight: 800; text-align: right">{{ $value }}</b>
                    </div>
                @endforeach
                <details class="md:pt-3" style="font-size: 14px; line-height: 20px">
                    <summary style="cursor: pointer; font-weight: 700; color: var(--ink-2)">{{ plural_uk($order->items->sum('quantity'), 'товар', 'товари', 'товарів') }}</summary>
                    <ul style="margin: 8px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px">
                        @foreach ($order->items as $item)
                            <li style="display: flex; justify-content: space-between; gap: 12px">
                                <span>{{ $item->name }}@if ($item->variant_label)<span class="mut"> · {{ $item->variant_label }}</span>@endif<span class="mut"> · {{ $item->quantity }} шт</span></span>
                                <b class="tabular" style="white-space: nowrap">{{ money($item->total) }}</b>
                            </li>
                        @endforeach
                    </ul>
                </details>
            </div>

            {{-- Що далі --}}
            <div class="flex flex-col gap-3.5 md:gap-[18px] md:rounded-(--radius-xl) md:bg-(--surface) md:p-7">
                <h2 class="text-xl leading-[26px] md:text-lg md:leading-6" style="margin: 0; font-family: var(--font-display); font-weight: 800">Що далі</h2>
                <ol style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 14px">
                    @foreach ($steps as $i => [$title, $text])
                        <li style="display: flex; gap: 12px; align-items: flex-start">
                            <span style="width: 28px; height: 28px; flex: none; border-radius: 50%; background: var(--ink); color: var(--on-ink); display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800">{{ $i + 1 }}</span>
                            <span style="display: flex; flex-direction: column; gap: 2px">
                                <b style="font-size: 15px; line-height: 20px; font-weight: 800">{{ $title }}</b>
                                <span style="font-size: 14px; color: var(--ink-2); font-weight: 500">{{ $text }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <div class="flex flex-col gap-3 md:flex-row md:justify-center">
            <a class="bz-btn bz-btn--secondary bz-btn--l bz-btn--block only-mob" href="{{ $trackingUrl }}">Відстежити замовлення</a>
            <a class="bz-btn bz-btn--ghost bz-btn--block only-mob" href="{{ route('home') }}">На головну</a>
            <a class="bz-btn bz-btn--dark bz-btn--l only-desk" href="{{ $trackingUrl }}" style="padding: 0 32px">Відстежити замовлення</a>
            <a class="bz-btn bz-btn--secondary bz-btn--l only-desk" href="{{ route('home') }}" style="padding: 0 32px">Продовжити покупки</a>
        </div>
    </div>
</x-layouts.app>
