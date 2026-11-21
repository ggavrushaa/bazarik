{{-- «Доставка й оплата»: MobileDelivery (до 768px) / DeskDelivery (від 768px) --}}
@php
    $cutoff = config('shop.same_day_cutoff');
    $freeFrom = money(config('shop.free_delivery_from'));
    $percent = rtrim(rtrim(number_format((float) config('shop.cod_fee_percent'), 2, ',', ''), '0'), ',');
    $codFee = 'Комісія Нової Пошти '.$percent.'% + '.money(config('shop.cod_fee_fixed'));
    $methods = [
        ['Відділення', '1–2 дні по Україні', '≈ '.money(config('shop.delivery_cost'))],
        ['Поштомат', '1–2 дні · до 30 кг', '≈ '.money(config('shop.delivery_cost'))],
        ['Кур’єр додому', '1–2 дні · 9:00–21:00', '≈ '.money(config('shop.courier_cost'))],
    ];
    $payments = [
        ['phone', 'Apple Pay / Google Pay', 'Одним дотиком'],
        ['card', 'Карткою онлайн', 'Visa, Mastercard'],
        ['cash', 'При отриманні', $codFee],
    ];
    $faq = [
        ['Як відстежити посилку?', 'У день відправки надішлемо SMS з номером ТТН. Статус можна перевірити на сторінці «<a href="'.e(link_to_route_or_hash('tracking')).'">Де моє замовлення</a>» або в застосунку Нової Пошти.'],
        // TODO: shop.storage_days — скільки днів посилка безкоштовно чекає у відділенні (на погодженні)
        ['Скільки посилка чекає у відділенні?', 'Безкоштовно — [N] днів, далі Нова Пошта бере плату за зберігання. Нагадаємо в SMS.'],
        ['Можна змінити відділення після оформлення?', 'Так, якщо посилку ще не відправили. Напишіть нам у <a href="'.e(config('shop.viber')).'">Viber</a> або <a href="'.e(config('shop.telegram')).'">Telegram</a>.'],
    ];
    $deliveryNote = 'Від <b>'.e($freeFrom).'</b> доставка безкоштовна. Вартість нижче — за тарифом Нової Пошти, сплачуєте при отриманні.';
    $payNote = 'Можна відкрити посилку у відділенні, оглянути товар і лише тоді оплатити. Не підійшло — просто не забираєте.';
    $h2 = 'margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 20px; line-height: 26px';
@endphp
@component('pages.info.shell', ['title' => 'Доставка й оплата', 'active' => 'info.delivery', 'maxWidth' => '800px',
    'description' => 'Відправляємо Новою Поштою в день замовлення до '.$cutoff.'. Оплата карткою або після огляду у відділенні.'])
    @slot('mobile')
        <div class="bz-delivery bz-delivery--box"><x-bz.icon name="truck" size="22" /><span>Замовлення до {{ $cutoff }} відправляємо того ж дня<small>Наступного дня посилка вже у вашому відділенні</small></span></div>

        <h2 style="{{ $h2 }}">Доставка Новою Поштою</h2>
        <div>
            @foreach ($methods as [$name, $hint, $price])
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--line)">
                    <span style="display: flex; flex-direction: column; gap: 2px"><b style="font-size: 15px">{{ $name }}</b><span style="font-size: 13px; color: var(--ink-muted); font-weight: 600">{{ $hint }}</span></span>
                    <b style="font-size: 15px; white-space: nowrap">{{ $price }}</b>
                </div>
            @endforeach
        </div>
        <p style="margin: 0; font-size: 14px; line-height: 21px; font-weight: 500; color: var(--ink-2)">{!! $deliveryNote !!}</p>

        <h2 style="{{ $h2 }}">Оплата</h2>
        <div>
            @foreach ($payments as [, $name, $hint])
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--line)">
                    <span style="display: flex; flex-direction: column; gap: 2px"><b style="font-size: 15px">{{ $name }}</b><span style="font-size: 13px; color: var(--ink-muted); font-weight: 600">{{ $hint }}</span></span>
                </div>
            @endforeach
        </div>
        <p style="margin: 0; font-size: 14px; line-height: 21px; font-weight: 500; color: var(--ink-2)">{{ $payNote }}</p>

        <h2 style="{{ $h2 }}">Часті питання</h2>
        <div>
            @foreach ($faq as $i => [$q, $a])
                <details @if ($i === 0) open @endif style="border-bottom: 1px solid var(--line); padding: 14px 0">
                    <summary style="cursor: pointer; font-size: 15px; font-weight: 800; min-height: 24px">{{ $q }}</summary>
                    <p style="margin: 8px 0 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">{!! $a !!}</p>
                </details>
            @endforeach
        </div>

        @include('pages.info.questions')
    @endslot

    <div style="display: flex; flex-direction: column; gap: 16px">
        <h1 class="h1" style="font-size: 40px; line-height: 46px">Доставка й оплата</h1>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px">
            <div style="padding: 20px; border-radius: var(--radius-l); background: var(--surface-warm)"><b style="font-size: 17px; font-weight: 800">Замовлення до {{ $cutoff }} відправляємо того ж дня</b></div>
            <div style="padding: 20px; border-radius: var(--radius-l); background: var(--surface-warm)"><b style="font-size: 17px; font-weight: 800">Наступного дня посилка вже у вашому відділенні</b></div>
        </div>
    </div>

    <section style="display: flex; flex-direction: column; gap: 12px">
        <h2 class="h2">Доставка Новою Поштою</h2>
        @foreach ($methods as [$name, $hint, $price])
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; border: 1px solid var(--line); border-radius: var(--radius-l)">
                <span><b style="display: block; font-size: 16px; font-weight: 800">{{ $name }}</b><span class="mut">{{ $hint }}</span></span>
                <b style="font-size: 17px">{{ $price }}</b>
            </div>
        @endforeach
        <p class="p">{!! $deliveryNote !!}</p>
    </section>

    <section style="display: flex; flex-direction: column; gap: 12px">
        <h2 class="h2">Оплата</h2>
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px">
            @foreach ($payments as [$icon, $name, $hint])
                <div style="display: flex; flex-direction: column; gap: 8px; padding: 20px; border: 1px solid var(--line); border-radius: var(--radius-l)">
                    <x-bz.icon :name="$icon" size="24" style="color: var(--ink)" />
                    <b style="font-size: 16px; font-weight: 800">{{ $name }}</b>
                    <span class="mut">{{ $hint }}</span>
                </div>
            @endforeach
        </div>
        <p class="p">{{ $payNote }}</p>
    </section>

    <section>
        <h2 class="h2" style="margin-bottom: 8px">Часті питання</h2>
        @foreach ($faq as $i => [$q, $a])
            <details @if ($i === 0) open @endif style="border-bottom: 1px solid var(--line); padding: 18px 0">
                <summary style="cursor: pointer; font-size: 17px; line-height: 24px; font-weight: 800">{{ $q }}</summary>
                <p class="p" style="margin-top: 10px; max-width: 640px">{!! $a !!}</p>
            </details>
        @endforeach
    </section>

    @include('pages.info.questions', ['desk' => true])
@endcomponent
