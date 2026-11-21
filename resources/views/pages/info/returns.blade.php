{{-- «Обмін і повернення»: MobileReturns (до 768px) / DeskReturns (від 768px) --}}
@php
    $days = plural_uk(config('shop.return_days'), 'дня', 'днів', 'днів');
    $warranty = plural_uk(config('shop.warranty_months'), 'місяць', 'місяці', 'місяців');
    $viber = config('shop.viber');
    $intro = 'Не підійшов розмір, колір чи передумали — повернете протягом <b%s>'.e($days).'</b> після отримання.';
    $steps = [
        ['Напишіть нам', 'У Viber чи Telegram: номер замовлення і що повертаєте.'],
        ['Надішліть Новою Поштою', 'Дамо адресу складу. Товар без слідів використання, з бирками та в упаковці.'],
        // TODO: shop.refund_days — за скільки днів повертаємо гроші (на погодженні)
        ['Отримайте гроші', 'Повернемо на картку протягом [N] днів після того, як посилка дійде.'],
    ];
    $exchange = 'Потрібен інший розмір чи колір? Відправимо заміну одразу, як отримаємо ваш товар — або раніше, якщо він є на складі.';
    $defect = 'Надішліть фото чи відео — замінимо або повернемо гроші. Доставку в цьому випадку оплачуємо ми. На техніку діє гарантія '.$warranty.'.';
    $faq = [
        // TODO: shop.return_shipping_payer — хто платить за доставку повернення (на погодженні)
        ['Хто платить за доставку повернення?', 'Якщо товар не підійшов — ви, за тарифом Нової Пошти. Якщо брак або ми помилились — ми. [уточнити з клієнтом]'],
        ['Які товари не можна повернути?', 'За законом не повертаються окремі товари належної якості: білизна, товари для гігієни та деякі інші. Ми позначаємо їх на сторінці товару.'],
    ];
    $h2 = 'margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 20px; line-height: 26px';
    $text = 'margin: 0; font-size: 14px; line-height: 21px; font-weight: 500; color: var(--ink-2)';
@endphp
@component('pages.info.shell', ['title' => 'Обмін і повернення', 'active' => 'info.returns',
    'description' => 'Повернення протягом '.$days.' після отримання, обмін розміру чи кольору, гарантія '.$warranty.'.'])
    @slot('mobile')
        <p style="margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">{!! sprintf($intro, '') !!}</p>

        <h2 style="{{ $h2 }}">Як повернути</h2>
        <ol style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 14px">
            @foreach ($steps as $i => [$stepTitle, $hint])
                <li style="display: flex; gap: 12px; align-items: flex-start">
                    <span style="width: 28px; height: 28px; flex: none; border-radius: 99px; background: var(--ink); color: var(--on-ink); display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800">{{ $i + 1 }}</span>
                    <span style="display: flex; flex-direction: column; gap: 2px"><b style="font-size: 15px">{{ $stepTitle }}</b><span style="font-size: 14px; color: var(--ink-2); font-weight: 500; line-height: 20px">{{ $hint }}</span></span>
                </li>
            @endforeach
        </ol>

        <h2 style="{{ $h2 }}">Обмін</h2>
        <p style="{{ $text }}">{{ $exchange }}</p>
        <h2 style="{{ $h2 }}">Якщо товар з браком</h2>
        <p style="{{ $text }}">{{ $defect }}</p>

        <div>
            @foreach ($faq as $i => [$q, $a])
                <details @if ($i === 0) open @endif style="border-bottom: 1px solid var(--line); padding: 14px 0">
                    <summary style="cursor: pointer; font-size: 15px; font-weight: 800; min-height: 24px">{{ $q }}</summary>
                    <p style="margin: 8px 0 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">{{ $a }}</p>
                </details>
            @endforeach
        </div>
        <a class="bz-btn bz-btn--dark bz-btn--l bz-btn--block" href="{{ $viber }}">Оформити повернення у Viber</a>

        @include('pages.info.questions')
    @endslot

    <div style="display: flex; flex-direction: column; gap: 16px">
        <h1 class="h1" style="font-size: 40px; line-height: 46px">Обмін і повернення</h1>
        <div style="padding: 24px; border-radius: var(--radius-l); background: var(--surface-warm); font-size: 18px; line-height: 26px; font-weight: 600">{!! sprintf($intro, ' style="font-weight: 900"') !!}</div>
    </div>

    <section style="display: flex; flex-direction: column; gap: 16px">
        <h2 class="h2">Як повернути</h2>
        <ol class="grid grid-cols-1 gap-4 lg:grid-cols-3" style="margin: 0; padding: 0; list-style: none">
            @foreach ($steps as $i => [$stepTitle, $hint])
                <li style="display: flex; flex-direction: column; gap: 10px; padding: 24px; border-radius: var(--radius-l); border: 1px solid var(--line)">
                    <span style="width: 36px; height: 36px; border-radius: 50%; background: var(--ink); color: var(--on-ink); display: inline-flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800">{{ $i + 1 }}</span>
                    <b style="font-size: 17px; line-height: 22px; font-weight: 800">{{ $stepTitle }}</b>
                    <span class="p" style="font-size: 15px; line-height: 22px">{{ $hint }}</span>
                </li>
            @endforeach
        </ol>
        <a class="bz-btn bz-btn--dark bz-btn--l" href="{{ $viber }}" style="align-self: flex-start; padding: 0 28px">Оформити повернення у Viber</a>
    </section>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div style="display: flex; flex-direction: column; gap: 8px; padding: 24px; border-radius: var(--radius-l); border: 1px solid var(--line)">
            <h3 class="h3">Обмін</h3>
            <p class="p" style="font-size: 15px; line-height: 22px">{{ $exchange }}</p>
        </div>
        <div id="warranty" style="display: flex; flex-direction: column; gap: 8px; padding: 24px; border-radius: var(--radius-l); border: 1px solid var(--line); scroll-margin-top: 24px">
            <h3 class="h3">Якщо товар з браком</h3>
            <p class="p" style="font-size: 15px; line-height: 22px">{{ $defect }}</p>
        </div>
    </section>

    <section>
        <h2 class="h2" style="margin-bottom: 8px">Часті питання</h2>
        @foreach ($faq as $i => [$q, $a])
            <details @if ($i === 0) open @endif style="border-bottom: 1px solid var(--line); padding: 18px 0">
                <summary style="cursor: pointer; font-size: 17px; line-height: 24px; font-weight: 800">{{ $q }}</summary>
                <p class="p" style="margin-top: 10px; max-width: 640px">{{ $a }}</p>
            </details>
        @endforeach
    </section>

    @include('pages.info.questions', ['desk' => true])
@endcomponent
