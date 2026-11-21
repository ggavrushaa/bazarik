{{-- Нічого не знайшли: StateSearchEmpty (телефон) і DeskSearchEmpty (десктоп) --}}
@php
    $queries = ['термос', 'плед', 'павербанк', 'худі', 'лампа'];
    $heading = $q !== '' ? 'За запитом «'.$q.'» нічого немає' : 'Що шукаємо?';
    $text = $q !== ''
        ? 'Перевірте написання або спробуйте простіше слово. А ще можна спитати нас — підкажемо схожий товар.'
        : 'Напишіть назву товару в пошуку вгорі. А ще можна спитати нас — підкажемо, що підійде.';
@endphp

{{-- Телефон --}}
<div class="only-mob" style="display: flex; flex-direction: column; gap: 16px; padding: 24px 0 32px">
    <h2 style="margin: 0; font-family: var(--font-display); font-weight: 900; font-size: 24px; line-height: 30px">{{ $heading }}</h2>
    <p style="margin: 0; font-size: 15px; line-height: 22px; font-weight: 500; color: var(--ink-2)">{{ $text }}</p>
    <div style="font-size: 13px; font-weight: 800; color: var(--ink-muted); margin-top: 8px">ЧАСТО ШУКАЮТЬ</div>
    <div style="display: flex; flex-wrap: wrap; gap: 8px">
        @foreach ($queries as $query)
            <a class="bz-chip" href="{{ route('search', ['q' => $query]) }}" style="text-decoration: none">{{ $query }}</a>
        @endforeach
    </div>
    <a class="bz-btn bz-btn--secondary bz-btn--block" href="{{ config('shop.viber') }}"><x-bz.icon name="chat" />Спитати у Viber</a>
</div>

{{-- Десктоп --}}
<div class="only-desk" style="display: flex; flex-direction: column; gap: 48px; padding: 48px 0 40px">
    <section style="display: flex; flex-direction: column; align-items: center; gap: 16px; text-align: center; padding: 24px 0">
        <span style="width: 72px; height: 72px; border-radius: 50%; background: var(--surface); display: flex; align-items: center; justify-content: center"><x-bz.icon name="search" size="32" /></span>
        <h1 class="h1" style="font-size: 36px; line-height: 42px">{{ $heading }}</h1>
        <p class="p" style="max-width: 560px">{{ $text }}</p>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; margin-top: 8px">
            <span class="mut" style="font-size: 12px; letter-spacing: .06em; font-weight: 800">ЧАСТО ШУКАЮТЬ</span>
            <div class="bz-chips" style="justify-content: center">
                @foreach ($queries as $query)
                    <a class="bz-chip" href="{{ route('search', ['q' => $query]) }}" style="text-decoration: none">{{ $query }}</a>
                @endforeach
            </div>
        </div>
        <a class="bz-btn bz-btn--secondary bz-btn--l" href="{{ config('shop.viber') }}" style="margin-top: 8px; padding: 0 28px">Спитати у Viber</a>
    </section>

    @if ($popular->isNotEmpty())
        <section style="display: flex; flex-direction: column; gap: 24px">
            <div class="sechead"><h2 class="h2">Що беруть цього тижня</h2><a href="{{ route('home') }}">Усі товари →</a></div>
            <div class="g4">
                @foreach ($popular as $product)
                    <x-shop.product-card :product="$product" :sizes="true" wire:key="pop-{{ $product->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
