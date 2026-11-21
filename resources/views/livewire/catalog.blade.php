{{--
    Каталог: MobileCatalog (до 768px), DeskCatalog (від 1024px), між ними — десктоп без бічної колонки,
    фільтри в панелі. Той самий компонент — для «Знижок» і пошуку (StateSearch / DeskSearchResults).
--}}
@php
    $isSearch = $mode === 'search';
    $count = plural_uk($total, 'товар', 'товари', 'товарів');
    $shown = $products->count();
    $next = min(\App\Livewire\Catalog::PER_PAGE, $total - $shown);
    // На телефоні підкатегорії — окремим рядком чипів, тому в «Фільтри · N» і чипах обраного їх не рахуємо
    $mobSubchips = $mode === 'category' && $types->isNotEmpty();
    $mobActive = $mobSubchips ? array_values(array_filter($active, fn ($a) => $a['facet'] !== 'type')) : $active;
    $parent = $category?->parent;
    $mobCrumbs = 'font-size: 13px; font-weight: 600; color: var(--ink-muted)';
    $mobChip = 'display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 10px 0 14px; border: 0; border-radius: var(--radius-full); background: var(--surface-warm); color: var(--ink); font: 700 13px/1 var(--font-sans); cursor: pointer';
    $resetLink = 'border: 0; background: transparent; font: 700 14px/1 var(--font-sans); color: var(--brand-ink); text-decoration: underline; cursor: pointer';
    $deskSelect = 'appearance: none; -webkit-appearance: none; height: 44px; padding: 0 40px 0 14px; border: 1.5px solid var(--line-strong); border-radius: var(--radius-m); background: var(--bg); color: var(--ink); font: 700 15px/1 var(--font-sans); cursor: pointer';
@endphp
<div class="wrap" x-data="{ filters: false }" x-on:keydown.escape.window="filters = false" style="display: flex; flex-direction: column">

@if ($isSearch && $total === 0)
    @include('livewire.catalog-search-empty')
@elseif ($isSearch)
    {{-- ── Результати пошуку ── --}}
    <div class="flex flex-col gap-4 pt-1 pb-8 md:gap-8 md:pt-8 md:pb-10">
        <p class="only-mob" style="margin: 0; font-size: 14px; line-height: 21px; font-weight: 500; color: var(--ink-2)">Показуємо результати для «<b>{{ $search['display'] }}</b>» · {{ $count }}</p>
        <div class="only-desk" style="display: flex; flex-direction: column; gap: 16px">
            <h1 class="h1" style="font-size: 36px; line-height: 42px">Результати для «{{ $search['display'] }}»</h1>
            <span class="mut" style="font-size: 15px">{{ $count }}@if ($search['also']) · шукали також {!! collect($search['also'])->map(fn ($v) => '«'.e($v).'»')->implode(', ') !!}@endif</span>
        </div>

        <div class="flex items-center justify-between gap-3 md:-mt-4">
            <div class="bz-chips" role="group" aria-label="Уточнити пошук" style="flex: 1; min-width: 0">
                <button class="bz-chip" type="button" aria-pressed="{{ $selected['type'] === [] ? 'true' : 'false' }}" wire:click="setType('')">Усі<span class="only-desk"> · {{ $baseTotal }}</span></button>
                @foreach ($types->count() > 1 ? $types : [] as $t)
                    <button class="bz-chip" type="button" wire:key="st-{{ $t['slug'] }}" aria-pressed="{{ $selected['type'] === [$t['slug']] ? 'true' : 'false' }}" wire:click="setType({{ \Illuminate\Support\Js::from($t['slug']) }})">{{ $t['name'] }}</button>
                @endforeach
            </div>
            <span class="only-desk" style="position: relative; flex: none">
                <label class="sr" for="search-sort">Сортування</label>
                <select id="search-sort" wire:model.live="sort" style="{{ $deskSelect }}">
                    @foreach ($sorts as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                <x-bz.icon name="chevron-down" size="18" style="position: absolute; right: 14px; top: 13px; pointer-events: none" />
            </span>
        </div>

        <div class="g4" wire:loading.class="opacity-60">
            @foreach ($products as $product)
                <x-shop.product-card :product="$product" :sizes="true" wire:key="p-{{ $product->id }}" />
            @endforeach
        </div>

        @include('livewire.catalog-more')
    </div>
@else
    {{-- ── Категорія / Знижки ── --}}
    <div class="flex flex-col gap-4 pt-3 pb-10 md:gap-6 md:pt-8 md:pb-[72px]">
        {{-- Заголовок: телефон --}}
        <nav class="only-mob" aria-label="Ви тут" style="{{ $mobCrumbs }}">
            <a href="{{ route('home') }}" style="color: var(--ink-muted)">Головна</a>
            @if ($parent) / <a href="{{ route('catalog', $parent) }}" style="color: var(--ink-muted)">{{ $parent->name }}</a>@endif
            / {{ $title }}
        </nav>
        <div class="only-mob" style="display: flex; align-items: baseline; justify-content: space-between; gap: 12px">
            <h1 style="margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 26px; line-height: 32px; letter-spacing: -0.02em">{{ $title }}</h1>
            <span style="font-size: 14px; font-weight: 700; color: var(--ink-muted); white-space: nowrap">{{ $count }}</span>
        </div>

        {{-- Заголовок: десктоп --}}
        <div class="only-desk" style="display: flex; flex-direction: column; gap: 12px">
            <nav class="crumbs" aria-label="Навігація">
                <a href="{{ route('home') }}">Головна</a><span>/</span>
                @if ($parent)<a href="{{ route('catalog', $parent) }}">{{ $parent->name }}</a><span>/</span>@endif
                <span>{{ $title }}</span>
            </nav>
            <div style="display: flex; align-items: baseline; gap: 14px">
                <h1 class="h1" style="font-size: 40px; line-height: 46px">{{ $title }}</h1>
                <span class="mut" style="font-size: 15px">{{ $count }}</span>
            </div>
        </div>

        {{-- Телефон: підкатегорії, «Фільтри · N» + сортування, обрані фільтри --}}
        @if ($mobSubchips)
            <div class="bz-chips only-mob" role="group" aria-label="Підкатегорії">
                <button class="bz-chip" type="button" aria-pressed="{{ $selected['type'] === [] ? 'true' : 'false' }}" wire:click="setType('')">Усі</button>
                @foreach ($types as $t)
                    <button class="bz-chip" type="button" wire:key="mt-{{ $t['slug'] }}" aria-pressed="{{ $selected['type'] === [$t['slug']] ? 'true' : 'false' }}" wire:click="setType({{ \Illuminate\Support\Js::from($t['slug']) }})">{{ $t['name'] }}</button>
                @endforeach
            </div>
        @endif

        <div class="only-mob" style="display: flex; gap: 8px; align-items: center">
            <button class="bz-btn bz-btn--secondary" type="button" style="flex: 1" x-on:click="filters = true" aria-haspopup="dialog">
                <x-bz.icon name="filter" />
                Фільтри{{ $mobActive ? ' · '.count($mobActive) : '' }}
            </button>
            <label class="sr" for="cat-sort">Сортування</label>
            <select id="cat-sort" wire:model.live="sort" style="flex: 1; min-width: 0; height: 44px; padding: 0 12px; border: 1.5px solid var(--line-strong); border-radius: var(--radius-full); background: var(--bg); color: var(--ink); font: 700 14px/1 var(--font-sans)">
                @foreach ($sorts as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
        </div>

        @if ($mobActive)
            <div class="only-mob" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center" aria-label="Обрані фільтри">
                @foreach ($mobActive as $a)
                    <button type="button" wire:key="ma-{{ $a['facet'] }}-{{ $loop->index }}" aria-label="Прибрати фільтр {{ $a['label'] }}" style="{{ $mobChip }}"
                            wire:click="remove('{{ $a['facet'] }}', {{ \Illuminate\Support\Js::from($a['value']) }})">
                        {{ $a['label'] }}
                        <x-bz.icon name="close" size="16" stroke="2.4" />
                    </button>
                @endforeach
                <button type="button" style="{{ $resetLink }}; height: 36px; padding: 0 6px; font-size: 13px" wire:click="resetFilters">Скинути все</button>
            </div>
        @endif

        <div class="lg:grid lg:grid-cols-[264px_minmax(0,1fr)] lg:gap-10">
            {{-- Десктоп від 1024px: фільтри зліва --}}
            <aside aria-label="Фільтри" class="hidden lg:flex lg:flex-col">
                @include('livewire.catalog-filters', ['p' => 'd'])
            </aside>

            <div style="display: flex; flex-direction: column; gap: 24px; min-width: 0">
                {{-- Десктоп: обрані фільтри + сортування --}}
                <div class="only-desk" style="display: flex; align-items: center; gap: 12px">
                    <div class="bz-chips" style="flex: 1; min-width: 0; align-items: center">
                        @foreach ($active as $a)
                            <button class="bz-chip" type="button" aria-pressed="true" style="gap: 6px" wire:key="da-{{ $a['facet'] }}-{{ $loop->index }}"
                                    aria-label="Прибрати фільтр {{ $a['label'] }}"
                                    wire:click="remove('{{ $a['facet'] }}', {{ \Illuminate\Support\Js::from($a['value']) }})">{{ $a['label'] }}<x-bz.icon name="close" size="14" /></button>
                        @endforeach
                        @if ($active)
                            <button type="button" style="{{ $resetLink }}; text-decoration: none; margin-left: 4px; padding: 0; flex: none" wire:click="resetFilters">Скинути все</button>
                        @endif
                    </div>
                    <span class="lg:hidden" style="flex: none">
                        <button class="bz-btn bz-btn--secondary" type="button" x-on:click="filters = true" aria-haspopup="dialog">
                            <x-bz.icon name="filter" />Фільтри{{ $active ? ' · '.count($active) : '' }}
                        </button>
                    </span>
                    <label class="mut" for="sort" style="font-size: 14px; flex: none">Сортування</label>
                    <span style="position: relative; flex: none">
                        <select id="sort" wire:model.live="sort" style="{{ $deskSelect }}">
                            @foreach ($sorts as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                        <x-bz.icon name="chevron-down" size="18" style="position: absolute; right: 14px; top: 13px; pointer-events: none" />
                    </span>
                </div>

                @if ($total === 0)
                    {{-- Порожній результат: пропонуємо скинути фільтри --}}
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; padding: 40px 20px; border-radius: var(--radius-xl); background: var(--surface)">
                        <span style="width: 64px; height: 64px; border-radius: 50%; background: var(--bg); display: flex; align-items: center; justify-content: center"><x-bz.icon name="{{ $active ? 'filter' : 'box' }}" size="28" /></span>
                        @if ($active)
                            <h2 class="h3">За цими фільтрами нічого немає</h2>
                            <p class="p" style="max-width: 420px">Спробуйте прибрати частину фільтрів або скиньте всі — покажемо все, що є на складі.</p>
                            <button class="bz-btn bz-btn--primary" type="button" wire:click="resetFilters" style="padding: 0 28px">Скинути фільтри</button>
                        @else
                            <h2 class="h3">Тут поки порожньо</h2>
                            <p class="p" style="max-width: 420px">Товари в цьому розділі скоро з’являться. Подивіться інші розділи каталогу.</p>
                            <a class="bz-btn bz-btn--secondary" href="{{ route('home') }}" style="padding: 0 28px">На головну</a>
                        @endif
                    </div>
                @else
                    <div class="g3 md:[&_.bz-dots]:hidden!" wire:loading.class="opacity-60">
                        @foreach ($products as $product)
                            <x-shop.product-card :product="$product" :dots="true" :sizes="true" :size="count($selected['size']) === 1 ? $selected['size'][0] : null" wire:key="p-{{ $product->id }}" />
                        @endforeach
                    </div>
                    @include('livewire.catalog-more')
                @endif
            </div>
        </div>
    </div>

    {{-- Фільтри до 1024px: шторка знизу на телефоні, панель справа на планшеті --}}
    <div x-show="filters" x-cloak class="lg:hidden">
        <div class="scrim" x-on:click="filters = false"></div>
        <div role="dialog" aria-modal="true" aria-labelledby="flt-title"
             class="fixed inset-x-0 bottom-0 z-60 flex max-h-[92dvh] flex-col rounded-t-(--radius-xl) bg-bg shadow-(--shadow-pop) md:inset-y-0 md:left-auto md:w-[400px] md:max-h-none md:rounded-none"
             x-trap.noscroll="filters">
            <div style="padding: 8px 16px 0">
                <div class="flex justify-center pb-1 md:hidden"><span style="width: 40px; height: 4px; border-radius: 99px; background: var(--line-strong)"></span></div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 48px">
                    <h2 id="flt-title" style="margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 22px; line-height: 28px">Фільтри</h2>
                    <button class="bz-icon-btn" type="button" aria-label="Закрити фільтри" style="background: transparent" x-on:click="filters = false"><x-bz.icon name="close" size="22" /></button>
                </div>
            </div>
            <div style="flex: 1; overflow-y: auto; padding: 0 16px; display: flex; flex-direction: column">
                @include('livewire.catalog-filters', ['p' => 'm'])
            </div>
            <div style="display: flex; gap: 8px; padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px)); border-top: 1px solid var(--line)">
                @if ($active)
                    <button class="bz-btn bz-btn--secondary" type="button" wire:click="resetFilters">Скинути все</button>
                @endif
                <button class="bz-btn bz-btn--primary" type="button" style="flex: 1" x-on:click="filters = false">
                    {{ $total ? 'Показати '.$count : 'Нічого не знайдено' }}
                </button>
            </div>
        </div>
    </div>
@endif
</div>
