@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FF7A1A">
    <title>{{ $title ? $title.' — Bazarik' : 'Bazarik — усе для дому, завтра у вас' }}</title>
    <meta name="description" content="{{ $description ?? 'Товари вже на складі в Україні: відправляємо в день замовлення, оплата після огляду у відділенні.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh">
    {{-- Обіцянка зверху — «склад в Україні» має бути видно на кожному екрані --}}
    <div class="bg-ink text-center text-sm font-semibold text-white">
        <div class="container-shop py-2">
            Безкоштовна доставка від {{ money(config('shop.free_delivery_from')) }}
        </div>
    </div>

    <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur">
        <div class="container-shop flex h-16 items-center gap-4">
            <a href="{{ route('home') }}" class="text-ink" aria-label="На головну">
                <x-logo class="h-10" />
            </a>

            <form action="#" class="hidden flex-1 md:block" role="search">
                <input type="search" name="q" placeholder="Пошук: павербанк, термос…"
                       class="h-11 w-full rounded-2xl border border-line bg-surface px-4 text-base outline-none focus:border-brand focus:bg-white">
            </form>

            <nav class="ml-auto flex items-center gap-1 text-sm font-bold">
                <a href="#" class="rounded-xl px-3 py-2 hover:bg-surface">Увійти</a>
                <a href="#" class="rounded-xl px-3 py-2 hover:bg-surface">Кошик</a>
            </nav>
        </div>

        <div class="container-shop pb-3 md:hidden">
            <input type="search" placeholder="Пошук: павербанк, термос…"
                   class="h-11 w-full rounded-2xl border border-line bg-surface px-4 text-base outline-none focus:border-brand focus:bg-white">
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-line bg-surface">
        <div class="container-shop grid gap-6 py-10 text-sm md:grid-cols-3">
            <div>
                <x-logo class="h-9 text-ink" />
                <p class="mt-3 text-muted">Склад в Україні. Відправляємо в день замовлення до {{ config('shop.same_day_cutoff') }}.</p>
            </div>
            <ul class="space-y-2 font-semibold">
                <li><a href="#" class="hover:text-brand">Доставка й оплата</a></li>
                <li><a href="#" class="hover:text-brand">Обмін і повернення</a></li>
                <li><a href="#" class="hover:text-brand">Контакти</a></li>
            </ul>
            <p class="text-muted">© {{ date('Y') }} Bazarik · bazarik.com.ua</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
