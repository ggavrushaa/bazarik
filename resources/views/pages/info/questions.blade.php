{{-- «Залишились питання?» внизу інфосторінок; $desk — десктопний варіант --}}
@if ($desk ?? false)
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 24px; border-radius: var(--radius-l); background: var(--surface)">
        <span><b style="display: block; font-size: 17px; font-weight: 800">Залишились питання?</b><span class="mut">Пишіть — відповідаємо до 10 хвилин, {{ config('shop.hours_short') }}</span></span>
        <span style="display: flex; gap: 8px"><a class="bz-btn bz-btn--secondary" href="{{ config('shop.viber') }}">Viber</a><a class="bz-btn bz-btn--secondary" href="{{ config('shop.telegram') }}">Telegram</a></span>
    </div>
@else
    <section style="display: flex; flex-direction: column; gap: 10px; padding: 16px; border-radius: 16px; background: var(--surface)">
        <b style="font-size: 16px">Залишились питання?</b>
        <span style="font-size: 14px; color: var(--ink-2); font-weight: 500">Пишіть — відповідаємо до 10 хвилин, {{ config('shop.hours_short') }}</span>
        <div style="display: flex; gap: 8px"><a class="bz-btn bz-btn--secondary" href="{{ config('shop.viber') }}" style="flex: 1">Viber</a><a class="bz-btn bz-btn--secondary" href="{{ config('shop.telegram') }}" style="flex: 1">Telegram</a></div>
    </section>
@endif
