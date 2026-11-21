{{-- 404 «Ця ятка зачинена»: State404 (до 768px) / Desk404 (від 768px). Звичайний макет вітрини з шапкою й підвалом. --}}
@php
    $categories = rescue(fn () => \App\Models\Category::query()->active()->root()->orderBy('sort')->take(6)->get(), collect(), false);
    $catalogUrl = $categories->isNotEmpty() ? route('catalog', $categories->first()) : route('home');
@endphp
<x-layouts.app title="Сторінку не знайдено">
    <div class="pt-10 pb-8 md:pt-14 md:pb-[72px]">
    <div class="wrap flex flex-col gap-10">
        <section class="flex flex-col gap-[14px] px-2 md:items-center md:px-0 md:py-6 md:text-center">
            <div style="display: flex; justify-content: center">
                <svg width="200" height="150" viewBox="0 0 160 124" role="img" aria-label="Зачинена ятка з табличкою">
                    <rect x="22" y="40" width="5" height="78" rx="2.5" fill="#1f1f24"></rect>
                    <rect x="133" y="40" width="5" height="78" rx="2.5" fill="#1f1f24"></rect>
                    <path d="M23.8 18.0H46.3L39.2 38.4A13.6 13.6 0 0 1 12.0 38.4Z" fill="#c9c9d2"></path>
                    <path d="M46.3 18.0H68.8L66.4 38.4A13.6 13.6 0 0 1 39.2 38.4Z" fill="#a9a9b4"></path>
                    <path d="M68.8 18.0H91.2L93.6 38.4A13.6 13.6 0 0 1 66.4 38.4Z" fill="#c9c9d2"></path>
                    <path d="M91.2 18.0H113.7L120.8 38.4A13.6 13.6 0 0 1 93.6 38.4Z" fill="#a9a9b4"></path>
                    <path d="M113.7 18.0H136.2L148.0 38.4A13.6 13.6 0 0 1 120.8 38.4Z" fill="#c9c9d2"></path>
                    <rect x="14" y="104" width="132" height="12" rx="4" fill="#1f1f24"></rect>
                    <path d="M66 58L80 48l14 10" fill="none" stroke="#1f1f24" stroke-width="2" stroke-linecap="round"></path>
                    <circle cx="80" cy="48" r="2.5" fill="#1f1f24"></circle>
                    <rect x="50" y="58" width="60" height="26" rx="6" fill="#ff7a1a"></rect>
                    <text x="80" y="75.5" text-anchor="middle" font-family="Nunito Variable, Nunito, sans-serif" font-weight="900" font-size="12" fill="#1f1f24">ЗАЧИНЕНО</text>
                </svg>
            </div>
            <h1 class="h1 mt-2 text-center text-[26px] leading-8 md:mt-0 md:text-4xl md:leading-[42px]">Ця ятка зачинена</h1>
            <p class="p only-mob" style="text-align: center; font-size: 15px; line-height: 22px">Сторінки немає: можливо, товар закінчився або посилання застаріло. Решта базару працює.</p>
            <p class="p only-desk">Сторінки немає або її перенесли. Спробуйте пошук чи загляньте в каталог.</p>

            <form class="bz-search only-mob" role="search" action="{{ link_to_route_or_hash('search') }}" style="margin-top: 8px">
                <label class="sr" for="q4">Шукати товари</label>
                <input id="q4" type="search" name="q" placeholder="Що шукаєте?">
                <button class="bz-search__btn" type="submit" aria-label="Шукати"><x-bz.icon name="search" size="18" /></button>
            </form>
            <a class="bz-btn bz-btn--primary bz-btn--l bz-btn--block only-mob" href="{{ route('home') }}">На головну</a>

            <div class="only-desk" style="display: flex; gap: 12px; margin-top: 8px">
                <a class="bz-btn bz-btn--primary bz-btn--l" href="{{ $catalogUrl }}" style="padding: 0 32px">До каталогу</a>
                <a class="bz-btn bz-btn--secondary bz-btn--l" href="{{ route('home') }}" style="padding: 0 32px">На головну</a>
            </div>
        </section>

        @if ($categories->isNotEmpty())
            <nav class="only-desk" aria-label="Категорії">
                <div class="grid grid-cols-3 gap-6 lg:grid-cols-6">
                    @foreach ($categories as $category)
                        <a href="{{ route('catalog', $category) }}" style="display: flex; flex-direction: column; gap: 12px; text-decoration: none; color: var(--ink)">
                            <div class="img" style="aspect-ratio: 1 / 1; border-radius: var(--radius-l)">@if ($category->image_url)<img src="{{ $category->image_url }}" alt="">@endif</div>
                            <span style="font-size: 16px; line-height: 20px; font-weight: 800; text-align: center">{{ $category->name }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif
    </div>
    </div>
</x-layouts.app>
