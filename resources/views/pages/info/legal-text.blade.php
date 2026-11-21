{{-- Тіло юридичної сторінки: поки заглушка + реквізити продавця з config('shop.*') --}}
<div style="display: flex; flex-direction: column; gap: 16px">
    <p style="margin: 0; padding: 20px; border-radius: var(--radius-l); background: var(--surface-warm); font-size: 16px; line-height: 24px; font-weight: 700; color: var(--ink)">{{ $placeholder }}</p>
    <p class="p" style="font-size: 15px; line-height: 22px">Продавець: {{ config('shop.company') }}. Питання — {{ config('shop.email') }} або {{ config('shop.phone') }}, {{ config('shop.hours_short') }}.</p>
</div>
