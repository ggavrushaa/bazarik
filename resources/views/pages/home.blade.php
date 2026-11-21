{{-- Головна: MobileHome (до 768px) і DeskHome (від 768px) --}}
@php
    $cutoff = config('shop.same_day_cutoff');
    $returnDays = config('shop.return_days');
    $warranty = config('shop.warranty_months');
    $firstCategory = $categories->first();
    $catalogUrl = $firstCategory ? route('catalog', $firstCategory) : '#';
    $h2 = 'margin: 0; font-family: var(--font-display); font-weight: 800; letter-spacing: -0.01em';
    $faq = [
        ['Звідки відправляються товари?', 'З нашого складу в '.config('shop.warehouse_city').'. Усі товари вже тут, тому відправляємо в день замовлення.'],
        ['Чи можна оглянути перед оплатою?', 'Так. Оберіть оплату при отриманні: відкриваєте посилку у відділенні й платите, лише якщо все гаразд.'],
        ['Що, якщо товар зламається?', "На техніку діє гарантія {$warranty} місяців. Напишіть нам у Viber чи Telegram — замінимо або повернемо гроші."],
        ['Чи потрібна реєстрація?', 'Ні. Достатньо телефону й відділення. Якщо увійдете за номером, побачите історію замовлень і обране.'],
    ];
    $steps = [
        ['Оформлюєте за хвилину', 'Без реєстрації — лише телефон і відділення.'],
        ['Відправляємо того ж дня', "Замовлення до {$cutoff} їдуть сьогодні."],
        ['Оглядаєте й тоді платите', "Не підійшло — повернення {$returnDays} днів."],
    ];
    // Відгуки — заглушки з макета, доки не буде перших замовлень
    $reviews = [['p-plaid.jpg', 'p-thermos.jpg'], ['p-hoodie.jpg', 'p-sweatshirt.jpg'], ['p-powerbank-20000.jpg', 'p-led-lamp.jpg']];
@endphp
<x-layouts.app>
    <div class="wrap flex flex-col gap-8 pt-4 md:gap-14 md:pt-8">

        {{-- ── Перший екран ── --}}
        <section class="only-mob" style="display: flex; flex-direction: column; gap: 14px">
            <div class="img" style="aspect-ratio: 16 / 10; border-radius: var(--radius-xl); background: var(--surface-warm)">
                <img src="{{ asset('images/demo/hero-kitchen.jpg') }}" alt="Затишна кухня">
            </div>
            <h1 style="margin: 4px 0 0; font-family: var(--font-display); font-weight: 900; font-size: 32px; line-height: 36px; letter-spacing: -0.01em; text-wrap: balance">Усе для дому&nbsp;— завтра у&nbsp;вас</h1>
            <p style="margin: 0; font-size: 16px; line-height: 24px; font-weight: 500; color: var(--ink-2)">Платите у відділенні, коли все оглянули.</p>
            <a class="bz-btn bz-btn--primary bz-btn--l bz-btn--block" href="{{ $catalogUrl }}">До каталогу</a>
        </section>

        <section class="only-desk grid items-center gap-8 lg:grid-cols-[500px_1fr] lg:gap-12 md:grid-cols-[1fr_1fr]">
            <div style="display: flex; flex-direction: column; gap: 20px">
                <h1 class="h1 text-[44px] leading-[48px] lg:text-[60px] lg:leading-[64px]">Усе для дому&nbsp;— завтра у&nbsp;вас</h1>
                <p class="p" style="font-size: 19px; line-height: 28px">Від термоса до худі. Склад в Україні, відправляємо того ж дня, а платите у відділенні — коли все оглянули.</p>
                <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px">
                    <a class="bz-btn bz-btn--primary bz-btn--l" href="{{ $catalogUrl }}" style="padding: 0 32px">До каталогу</a>
                    <a class="bz-btn bz-btn--secondary bz-btn--l" href="#how" style="padding: 0 28px">Як ми працюємо</a>
                </div>
            </div>
            <div class="img h-[360px] lg:h-[440px]" style="border-radius: var(--radius-xl)">
                <img src="{{ asset('images/demo/hero-kitchen.jpg') }}" alt="Затишна кухня">
            </div>
        </section>

        {{-- ── Обіцянки: на телефоні після категорій, на десктопі одразу під першим екраном ── --}}
        <section aria-label="Наші обіцянки" class="only-desk grid grid-cols-2 gap-6 lg:grid-cols-4" style="padding: 20px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line)">
            @foreach ([
                ['truck', 'Завтра у відділенні', "замовлення до {$cutoff} — сьогодні в дорозі"],
                ['cash', 'Оплата після огляду', 'відкриваєте посилку й тоді платите'],
                ['return', "{$returnDays} днів на повернення", 'без пояснень, у будь-якому відділенні'],
                ['shield', "Гарантія {$warranty} місяців", 'на техніку й електроніку'],
            ] as [$icon, $title, $text])
                <div style="display: flex; align-items: center; gap: 14px">
                    <span style="width: 44px; height: 44px; flex: none; border-radius: 50%; background: var(--surface); display: flex; align-items: center; justify-content: center"><x-bz.icon :name="$icon" size="22" /></span>
                    <span style="display: flex; flex-direction: column"><b style="font-size: 15px; line-height: 20px; font-weight: 800">{{ $title }}</b><span class="mut">{{ $text }}</span></span>
                </div>
            @endforeach
        </section>

        {{-- ── Категорії ── --}}
        <section aria-label="Категорії" class="flex flex-col gap-6">
            <div class="sechead only-desk"><h2 class="h2">Категорії</h2></div>
            <div class="grid grid-cols-3 gap-[10px] md:gap-6 lg:grid-cols-6">
                @foreach ($categories as $category)
                    <a href="{{ route('catalog', $category) }}" class="flex flex-col gap-[6px] md:gap-3" style="text-decoration: none; color: var(--ink)">
                        <span class="img" style="aspect-ratio: 1 / 1; border-radius: var(--radius-l)">
                            @if ($category->image_url)<img src="{{ $category->image_url }}" alt="">@endif
                        </span>
                        <span class="text-[13px] leading-[17px] md:text-base md:leading-5 md:font-extrabold" style="font-weight: 700; text-align: center">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-label="Наші обіцянки" class="only-mob" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; padding: 14px 4px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line)">
            @foreach ([['truck', 'Завтра', 'у відділенні'], ['cash', 'Оплата', 'після огляду'], ['return', "{$returnDays} днів", 'на повернення']] as [$icon, $title, $text])
                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center">
                    <x-bz.icon :name="$icon" size="22" />
                    <span style="font-size: 13px; line-height: 16px; font-weight: 800">{{ $title }}</span>
                    <span style="font-size: 12px; line-height: 16px; font-weight: 600; color: var(--ink-muted)">{{ $text }}</span>
                </div>
            @endforeach
        </section>

        {{-- ── Популярне ── --}}
        <section id="popular" class="flex flex-col gap-4 md:gap-6">
            <div class="sechead">
                <h2 class="text-xl leading-[26px] md:text-[28px] md:leading-[34px]" style="{{ $h2 }}">{{ $popularTitle }}</h2>
                <a href="{{ $catalogUrl }}" class="only-mob" style="font-size: 14px">Усі →</a>
                <a href="{{ $catalogUrl }}" class="only-desk">Усі товари →</a>
            </div>
            <div class="g4">
                @foreach ($products as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        </section>

        {{-- ── Як ми працюємо ── --}}
        <section id="how" class="flex flex-col gap-4 md:gap-6">
            <h2 class="text-xl leading-[26px] md:text-[28px] md:leading-[34px]" style="{{ $h2 }}; text-wrap: balance">Як ми працюємо</h2>
            <ol class="grid gap-3 md:grid-cols-3 md:gap-6" style="margin: 0; padding: 0; list-style: none">
                @foreach ($steps as $i => [$title, $text])
                    <li class="flex gap-[14px] p-4 md:flex-col md:gap-[10px] md:p-6" style="align-items: flex-start; border-radius: var(--radius-l); border: 1px solid var(--line)">
                        <span class="size-8 md:size-9" style="flex: none; border-radius: 50%; background: var(--ink); color: var(--on-ink); display: inline-flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800">{{ $i + 1 }}</span>
                        <span style="display: flex; flex-direction: column; gap: 2px">
                            <b class="text-[15px] leading-[21px] md:text-lg md:leading-6" style="font-weight: 800">{{ $title }}</b>
                            <span class="text-sm leading-5 md:text-[15px] md:leading-[22px]" style="font-weight: 500; color: var(--ink-2)">{{ $text }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ── Відгуки ── --}}
        <section class="flex flex-col gap-4 md:gap-6">
            <h2 class="text-xl leading-[26px] md:text-[28px] md:leading-[34px]" style="{{ $h2 }}">Що кажуть покупці</h2>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($reviews as $i => $photos)
                    <div @class(['flex flex-col gap-[10px] p-4 md:gap-3 md:p-6', 'only-desk' => $i > 0]) style="border-radius: var(--radius-l); background: var(--surface)">
                        <div style="display: flex; justify-content: space-between; gap: 8px; font-size: 14px; font-weight: 800"><span>[Ім’я, місто]</span><span style="color: var(--brand-ink)">★★★★★</span></div>
                        <p style="margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">[Реальний відгук покупця з фото — додамо після перших замовлень]</p>
                        <div style="display: flex; gap: 8px">
                            @foreach ($photos as $photo)
                                <div class="img size-16 md:size-[72px]" style="border-radius: var(--radius-m); background: var(--line)"><img src="{{ asset('images/demo/'.$photo) }}" alt=""></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ── Часті питання ── --}}
        <section class="grid gap-2 md:grid-cols-[minmax(0,360px)_1fr] md:gap-12 md:pb-[72px]">
            <div class="flex flex-col gap-3">
                <h2 class="mb-2 text-xl leading-[26px] md:mb-0 md:text-[28px] md:leading-[34px]" style="{{ $h2 }}">Часті питання</h2>
                <p class="p only-desk">Не знайшли відповідь? Напишіть у Viber або Telegram — відповідаємо до 10 хвилин.</p>
            </div>
            <div>
                @foreach ($faq as $i => [$question, $answer])
                    <details @class(['py-3 md:py-[18px]', 'only-desk' => $i === 3]) style="border-bottom: 1px solid var(--line)" @if ($i === 0) open @endif>
                        <summary class="text-[15px] md:text-[17px] md:leading-6" style="cursor: pointer; font-weight: 800; min-height: 24px">{{ $question }}</summary>
                        <p class="mt-2 md:mt-[10px]" style="margin-bottom: 0; max-width: 640px; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
