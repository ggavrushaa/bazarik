{{--
    Каркас інфосторінок. До 768px — рядок «Назад + заголовок» і мобільний вміст ($mobile),
    від 768px — хлібні крихти, бокове меню «Покупцям» і десктопний вміст ($slot) (DeskDelivery / DeskReturns / DeskContacts).
    Використання: @component('pages.info.shell', ['title' => '…', 'active' => 'info.delivery']) @slot('mobile') … @endslot … @endcomponent
--}}
@php
    $previous = url()->previous();
    $back = $previous && $previous !== url()->current() && str_starts_with($previous, url('/')) ? $previous : route('home');
    $side = [
        ['Доставка й оплата', link_to_route_or_hash('info.delivery'), 'info.delivery'],
        ['Обмін і повернення', link_to_route_or_hash('info.returns'), 'info.returns'],
        ['Гарантія', link_to_route_or_hash('info.returns').'#warranty', null],
        ['Де моє замовлення', link_to_route_or_hash('tracking'), 'tracking'],
        ['Контакти', link_to_route_or_hash('info.contacts'), 'info.contacts'],
        ['Публічна оферта', link_to_route_or_hash('info.offer'), 'info.offer'],
        ['Політика конфіденційності', link_to_route_or_hash('info.privacy'), 'info.privacy'],
    ];
@endphp
<x-layouts.app :title="$title" :description="$description ?? null">
    {{-- ── Телефон ── --}}
    <div class="only-mob">
        <div class="bz-header__bar" style="border-bottom: 1px solid var(--line)">
            <a class="bz-icon-btn" href="{{ $back }}" aria-label="Назад"><x-bz.icon name="back" /></a>
            <h1 style="margin: 0 auto; font-size: 18px; line-height: 24px; font-weight: 800">{{ $title }}</h1>
            <span style="width: 44px" aria-hidden="true"></span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 20px; padding: 20px 16px 0">
            {{ $mobile ?? $slot }}
        </div>
    </div>

    {{-- ── Десктоп ── --}}
    <div class="wrap only-desk" style="display: flex; flex-direction: column; gap: 40px; padding-top: 32px">
        <nav class="crumbs" aria-label="Навігація"><a href="{{ route('home') }}">Головна</a><span>/</span><span>{{ $title }}</span></nav>
        <div class="grid grid-cols-[220px_1fr] gap-8 lg:grid-cols-[264px_1fr] lg:gap-12" style="margin-top: -32px; padding-bottom: 72px">
            <nav class="side" aria-label="Покупцям" style="display: flex; flex-direction: column; gap: 2px">
                @foreach ($side as [$label, $href, $route])
                    <a href="{{ $href }}" @class(['on' => $route && $route === ($active ?? null)]) @if ($route && $route === ($active ?? null)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div style="display: flex; flex-direction: column; gap: 40px; max-width: {{ $maxWidth ?? '840px' }}; min-width: 0">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
