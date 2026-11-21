{{--
    Макет вітрини. До 768px — мобільна шапка/підвал (MobileHome), від 768px — десктопні (DeskHome).
    header="checkout" — скорочена шапка оформлення (DeskCheckout / MobileCheckout).
    category — активна категорія для підсвітки в навігації.
--}}
@props(['title' => null, 'description' => null, 'header' => 'full', 'category' => null, 'footer' => true, 'headerTitle' => 'Оформлення', 'headerBack' => null])
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FF7A1A">
    <title>{{ $title ? $title.' — Bazarik' : 'Bazarik — усе для дому, завтра у вас' }}</title>
    <meta name="description" content="{{ $description ?? 'Товари вже на складі в Україні: відправляємо в день замовлення, оплата після огляду у відділенні.' }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
@php
    $activeRoot = $category?->parent ?? $category;
    $cutoff = config('shop.same_day_cutoff');
    $freeFrom = money(config('shop.free_delivery_from'));
    $customer = auth('customer')->user();
    $authModal = class_exists(\App\Livewire\AuthModal::class);
@endphp
<div x-data="{ menu: false, catalog: false }" x-on:keydown.escape.window="menu = false; catalog = false" style="min-height: 100dvh; display: flex; flex-direction: column">

    @if ($header === 'checkout')
        <x-shop.checkout-header :title="$headerTitle" :back="$headerBack" />
    @else
        {{-- ── Телефон ── --}}
        <header class="bz-header only-mob">
            <div class="bz-header__strip">
                <x-bz.icon name="truck" />
                Безкоштовна доставка від {{ $freeFrom }}
            </div>
            <div class="bz-header__bar">
                <button class="bz-icon-btn" type="button" aria-label="Меню" x-on:click="menu = true" :aria-expanded="menu"><x-bz.icon name="menu" stroke="2.2" /></button>
                <a class="bz-logo" href="{{ route('home') }}" aria-label="Bazarik, на головну"><x-logo /></a>
                <livewire:cart-badge variant="mob" />
            </div>
            <div class="bz-header__search">
                <form class="bz-search" role="search" action="{{ link_to_route_or_hash('search') }}">
                    <label class="sr" for="mq">Шукати товари</label>
                    <input id="mq" type="search" name="q" value="{{ request('q') }}" placeholder="Пошук: павербанк, термос…">
                    <button class="bz-search__btn" type="submit" aria-label="Шукати"><x-bz.icon name="search" stroke="2.4" /></button>
                </form>
            </div>
        </header>

        {{-- ── Десктоп ── --}}
        <header class="only-desk">
            <div class="dk-strip">
                <div class="wrap">
                    <span class="l"><x-bz.icon name="truck" size="16" />Безкоштовна доставка від {{ $freeFrom }} · замовлення до {{ $cutoff }} відправляємо сьогодні</span>
                    <nav aria-label="Покупцям">
                        <a href="{{ link_to_route_or_hash('info.delivery') }}">Доставка й оплата</a>
                        <a href="{{ link_to_route_or_hash('info.returns') }}">Обмін і повернення</a>
                        <a href="{{ link_to_route_or_hash('tracking') }}">Де моє замовлення</a>
                        <a href="{{ link_to_route_or_hash('info.contacts') }}">{{ config('shop.phone') }}</a>
                    </nav>
                </div>
            </div>
            <div class="wrap dk-bar">
                <a class="bz-logo" href="{{ route('home') }}" aria-label="Bazarik, на головну"><x-logo height="44" /></a>
                <button class="dk-catbtn" type="button" x-on:click="catalog = !catalog" :aria-expanded="catalog" aria-controls="catalog-menu"><x-bz.icon name="grid" size="20" />Каталог</button>
                <form class="dk-search" role="search" action="{{ link_to_route_or_hash('search') }}">
                    <label class="sr" for="dq">Шукати товари</label>
                    <input id="dq" type="search" name="q" value="{{ request('q') }}" placeholder="Пошук: павербанк, термос, худі…">
                    <button type="submit" aria-label="Шукати"><x-bz.icon name="search" size="20" stroke="2.4" /></button>
                </form>
                <nav class="dk-acts" aria-label="Профіль">
                    @if ($customer)
                        <a class="dk-act" href="{{ route('account') }}"><x-bz.icon name="user" size="24" />{{ $customer->first_name ?: 'Кабінет' }}</a>
                    @else
                        {{-- На десктопі вхід — модалка (DeskAuth); без JS — сторінка /login --}}
                        <a class="dk-act" href="{{ route('login') }}" @if ($authModal) x-on:click.prevent="Livewire.dispatch('open-auth')" @endif><x-bz.icon name="user" size="24" />Увійти</a>
                    @endif
                    <a class="dk-act" href="{{ link_to_route_or_hash('account.favorites') }}"><x-bz.icon name="heart" size="24" />Обране</a>
                    <livewire:cart-badge variant="desk" />
                </nav>
            </div>
            <nav class="dk-nav" aria-label="Категорії">
                <div class="wrap">
                    @foreach ($navCategories as $nav)
                        <a href="{{ route('catalog', $nav) }}" @class(['on' => $activeRoot?->is($nav)])>{{ $nav->name }}</a>
                    @endforeach
                    <a class="sale" href="{{ route('catalog.sale') }}">Знижки</a>
                </div>
            </nav>

            {{-- Каталог: категорії з підкатегоріями поверх сторінки (повне мега-меню — DeskMegaMenu, наступний етап) --}}
            <div x-show="catalog" x-cloak x-transition.opacity>
                <div class="scrim" style="top: 168px" x-on:click="catalog = false"></div>
                <div id="catalog-menu" style="position: absolute; left: 0; right: 0; z-index: 56; background: var(--bg); border-bottom: 1px solid var(--line); box-shadow: var(--shadow-pop)">
                    <div class="wrap" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px 40px; padding-top: 28px; padding-bottom: 32px">
                        @foreach ($navCategories as $nav)
                            <div style="display: flex; flex-direction: column; gap: 8px">
                                <a href="{{ route('catalog', $nav) }}" style="font-size: 17px; font-weight: 800; color: var(--ink); text-decoration: none">{{ $nav->name }}</a>
                                @foreach ($nav->children as $child)
                                    <a href="{{ route('catalog', $child) }}" style="font-size: 15px; font-weight: 600; color: var(--ink-2); text-decoration: none">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </header>

        {{-- Бокове меню телефона (StateMenu) --}}
        <div x-show="menu" x-cloak style="position: fixed; inset: 0; z-index: 70; display: flex; background: rgba(31,31,36,.45)" x-on:click.self="menu = false" x-transition.opacity>
            <nav aria-label="Меню" style="width: 320px; max-width: 85vw; height: 100%; overflow-y: auto; box-sizing: border-box; background: var(--bg); display: flex; flex-direction: column; padding: 12px 16px 24px; gap: 16px">
                <div style="display: flex; align-items: center; justify-content: space-between">
                    <a class="bz-logo" href="{{ route('home') }}" aria-label="Bazarik, на головну"><x-logo /></a>
                    <button class="bz-icon-btn" type="button" aria-label="Закрити меню" style="background: transparent" x-on:click="menu = false"><x-bz.icon name="close" /></button>
                </div>
                @if ($customer)
                    <a class="bz-btn bz-btn--secondary bz-btn--block" href="{{ route('account') }}">Кабінет{{ $customer->first_name ? ' · '.$customer->first_name : '' }}</a>
                @else
                    <a class="bz-btn bz-btn--secondary bz-btn--block" href="{{ route('login') }}">Увійти або зареєструватися</a>
                @endif
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .06em; color: var(--ink-muted)">КАТАЛОГ</div>
                <div>
                    @foreach ($navCategories as $nav)
                        <a href="{{ route('catalog', $nav) }}" style="display: flex; align-items: center; justify-content: space-between; min-height: 48px; padding: 0 4px; color: var(--ink); text-decoration: none; font-size: 16px; font-weight: 700; border-bottom: 1px solid var(--line)">{{ $nav->name }}<x-bz.icon name="chevron-right" size="18" /></a>
                    @endforeach
                </div>
                <div style="display: flex; flex-direction: column">
                    @foreach ([['tracking', 'Де моє замовлення'], ['info.delivery', 'Доставка й оплата'], ['info.returns', 'Обмін і повернення'], ['info.contacts', 'Контакти']] as [$name, $label])
                        <a href="{{ link_to_route_or_hash($name) }}" style="display: flex; align-items: center; min-height: 44px; padding: 0 4px; color: var(--ink-2); text-decoration: none; font-size: 15px; font-weight: 600">{{ $label }}</a>
                    @endforeach
                </div>
                <div style="margin-top: auto; display: flex; flex-direction: column; gap: 10px">
                    <div style="font-size: 13px; font-weight: 600; color: var(--ink-muted)">Питання? Пишіть, відповідаємо до 10 хвилин</div>
                    <div style="display: flex; gap: 8px">
                        <a class="bz-btn bz-btn--secondary" href="{{ config('shop.viber') }}" style="flex: 1">Viber</a>
                        <a class="bz-btn bz-btn--secondary" href="{{ config('shop.telegram') }}" style="flex: 1">Telegram</a>
                    </div>
                    {{-- Перемикач мови: EN — наступний етап --}}
                    <div style="display: inline-flex; align-self: flex-start; padding: 3px; border: 1px solid var(--line-strong); border-radius: 999px; font-size: 13px; font-weight: 800">
                        <span style="padding: 7px 14px; border-radius: 999px; background: var(--ink); color: #fff">UA</span>
                        <span style="padding: 7px 14px; color: var(--ink-muted)">EN</span>
                    </div>
                </div>
            </nav>
        </div>
    @endif

    <main style="flex: 1">
        {{ $slot }}
    </main>

    @if ($footer)
        {{-- ── Підвал: телефон ── --}}
        <footer class="only-mob" style="margin-top: 40px; padding: 32px 16px 40px; background: var(--ink); color: var(--on-ink); display: flex; flex-direction: column; gap: 20px">
            <span role="img" aria-label="bazarik" style="display: inline-flex"><x-logo variant="white" alt="" /></span>
            <p style="margin: 0; font-size: 14px; line-height: 20px; font-weight: 500; color: #d4d4dc">Речі для дому й родини. Доставка завтра, оплата після огляду.</p>
            <nav aria-label="Покупцям" style="display: flex; flex-direction: column; gap: 12px; font-size: 15px; font-weight: 700">
                <a href="{{ link_to_route_or_hash('info.delivery') }}" style="color: var(--on-ink); text-decoration: none">Доставка й оплата</a>
                <a href="{{ link_to_route_or_hash('info.returns') }}" style="color: var(--on-ink); text-decoration: none">Обмін і повернення</a>
                <a href="{{ link_to_route_or_hash('info.contacts') }}" style="color: var(--on-ink); text-decoration: none">Контакти</a>
            </nav>
            <nav aria-label="Документи" style="display: flex; flex-wrap: wrap; gap: 8px 16px; font-size: 13px; font-weight: 600">
                <a href="{{ link_to_route_or_hash('info.offer') }}" style="color: #d4d4dc">Публічна оферта</a>
                <a href="{{ link_to_route_or_hash('info.privacy') }}" style="color: #d4d4dc">Політика конфіденційності</a>
            </nav>
            <div style="font-size: 14px; line-height: 22px; font-weight: 600; color: #d4d4dc">{{ config('shop.phone') }} · Viber · Telegram<br>{{ config('shop.hours_short') }}</div>
            <div style="font-size: 12px; font-weight: 600; color: #a9a9b4">© {{ date('Y') }} Bazarik · {{ config('shop.company') }}</div>
        </footer>

        {{-- ── Підвал: десктоп ── --}}
        <footer class="dk-foot only-desk" id="contacts">
            <div class="wrap">
                <div class="cols">
                    <div style="display: flex; flex-direction: column; gap: 16px">
                        <span role="img" aria-label="bazarik" style="display: inline-flex"><x-logo variant="white" alt="" /></span>
                        <p style="margin: 0; max-width: 300px; font-size: 15px; line-height: 22px; font-weight: 500; color: #d4d4dc">Речі для дому й родини. Доставка завтра, оплата після огляду.</p>
                    </div>
                    <div>
                        <h4>КАТАЛОГ</h4>
                        <nav aria-label="Каталог">
                            @foreach ($navCategories as $nav)
                                <a href="{{ route('catalog', $nav) }}">{{ $nav->name }}</a>
                            @endforeach
                        </nav>
                    </div>
                    <div>
                        <h4>ПОКУПЦЯМ</h4>
                        <nav aria-label="Покупцям">
                            <a href="{{ link_to_route_or_hash('info.delivery') }}">Доставка й оплата</a>
                            <a href="{{ link_to_route_or_hash('info.returns') }}">Обмін і повернення</a>
                            <a href="{{ link_to_route_or_hash('tracking') }}">Де моє замовлення</a>
                            <a href="{{ link_to_route_or_hash('info.contacts') }}">Контакти</a>
                            <a href="{{ link_to_route_or_hash('account') }}">Особистий кабінет</a>
                            <a href="{{ link_to_route_or_hash('info.offer') }}">Публічна оферта</a>
                            <a href="{{ link_to_route_or_hash('info.privacy') }}">Політика конфіденційності</a>
                        </nav>
                    </div>
                    <div>
                        <h4>КОНТАКТИ</h4>
                        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 15px; font-weight: 600; color: #d4d4dc">
                            <span style="font-size: 20px; font-weight: 800; color: var(--on-ink)">{{ config('shop.phone') }}</span>
                            <span>{{ config('shop.hours') }}</span>
                            <span>Пишіть у Viber або Telegram — відповідаємо до 10 хвилин</span>
                            <span>{{ config('shop.email') }}</span>
                        </div>
                    </div>
                </div>
                <div class="base">
                    <span>© {{ date('Y') }} Bazarik · {{ config('shop.company') }}</span>
                    <span>Apple Pay · Google Pay · Visa · Mastercard · Оплата при отриманні</span>
                </div>
            </div>
        </footer>
    @endif
</div>

<livewire:cart-overlays />
{{-- Глобальні модалки етапу 2 (десктоп): вхід і «Купити в 1 клік» --}}
@if ($authModal)<livewire:auth-modal />@endif
@if (class_exists(\App\Livewire\QuickBuyModal::class))<livewire:quick-buy-modal />@endif
@livewireScripts
</body>
</html>
