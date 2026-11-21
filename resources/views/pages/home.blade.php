<x-layouts.app>
    {{-- Перший екран: коротке обіцяння + кнопка до каталогу --}}
    <section class="bg-brand-50">
        <div class="container-shop py-10 md:py-16">
            <h1 class="max-w-xl text-4xl font-black leading-tight md:text-5xl">Усе для дому —<br>завтра у вас</h1>
            <p class="mt-3 text-lg text-muted">Платите у відділенні, коли все оглянули.</p>
            <a href="#catalog" class="btn-primary mt-6">До каталогу</a>
        </div>
    </section>

    {{-- Категорії --}}
    <section id="catalog" class="container-shop mt-8">
        <div class="grid grid-cols-3 gap-3 md:grid-cols-6">
            @foreach ($categories as $category)
                <a href="#" class="flex aspect-square flex-col items-center justify-center rounded-2xl bg-surface p-3 text-center text-sm font-bold hover:bg-brand-100">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- Три обіцянки --}}
    <section class="container-shop mt-8 grid grid-cols-3 gap-3 text-center">
        <div class="rounded-2xl border border-line p-4">
            <div class="text-lg font-black">Завтра</div>
            <p class="text-xs text-muted md:text-sm">Відправка сьогодні до {{ config('shop.same_day_cutoff') }}</p>
        </div>
        <div class="rounded-2xl border border-line p-4">
            <div class="text-lg font-black">Оплата</div>
            <p class="text-xs text-muted md:text-sm">Після огляду у відділенні</p>
        </div>
        <div class="rounded-2xl border border-line p-4">
            <div class="text-lg font-black">{{ config('shop.return_days') }} днів</div>
            <p class="text-xs text-muted md:text-sm">На обмін і повернення</p>
        </div>
    </section>

    {{-- Хіти тижня --}}
    <section class="container-shop mt-10">
        <h2 class="text-2xl font-black">Хіти тижня</h2>
        <div class="mt-4 grid grid-cols-2 gap-x-3 gap-y-6 md:grid-cols-4">
            @foreach ($products as $product)
                <article class="group">
                    <div class="relative aspect-square overflow-hidden rounded-2xl bg-surface">
                        @if ($product->mainImage)
                            <img src="{{ $product->mainImage->url }}" alt="{{ $product->name }}" class="size-full object-cover" loading="lazy">
                        @endif
                        @if ($product->discount_percent)
                            <span class="absolute left-2 top-2 rounded-lg bg-brand px-2 py-0.5 text-xs font-black text-white">−{{ $product->discount_percent }}%</span>
                        @elseif ($product->badge)
                            <span class="absolute left-2 top-2 rounded-lg bg-ink px-2 py-0.5 text-xs font-black text-white">{{ $product->badge->getLabel() }}</span>
                        @endif
                    </div>
                    <h3 class="mt-2 line-clamp-2 text-sm font-semibold">{{ $product->name }}</h3>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-lg font-black">{{ money($product->price) }}</span>
                        @if ($product->old_price)
                            <span class="text-sm text-muted line-through">{{ money($product->old_price) }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.app>
