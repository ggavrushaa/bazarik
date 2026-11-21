{{--
    Навіс-завантажувач (StateLoading / DeskLoading): три фестони гойдаються, при prefers-reduced-motion стоять (.bz-loader у layout.css).
    <x-shop.loader />                                  — по центру, 54px, підпис «Розкладаємо товар…»
    <x-shop.loader card />                             — у білій картці з тінню поверх скелетона (десктоп)
    <x-shop.loader :label="null" size="40" />          — без підпису (для кнопок/невеликих блоків)
    З Livewire: <x-shop.loader wire:loading.flex wire:target="filters" />
--}}
@props(['label' => 'Розкладаємо товар…', 'size' => null, 'card' => false])
@php
    $width = (int) ($size ?? ($card ? 72 : 54));
    $height = (int) round($width * 12 / 30);
    $style = $card
        ? 'display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 24px 32px; border-radius: var(--radius-xl); background: var(--bg); box-shadow: var(--shadow-pop)'
        : 'display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 12px 0';
@endphp
<div role="status" aria-live="polite" {{ $attributes->merge(['style' => $style]) }}>
    <svg class="bz-loader" width="{{ $width }}" height="{{ $height }}" viewBox="0 0 30 12" @if ($label) aria-hidden="true" @else role="img" aria-label="Завантаження" @endif>
        <path d="M0 0h10v6a5 5 0 0 1-10 0z" fill="#ff7a1a"></path>
        <path d="M10 0h10v6a5 5 0 0 1-10 0z" fill="#d95e0a"></path>
        <path d="M20 0h10v6a5 5 0 0 1-10 0z" fill="#ff7a1a"></path>
    </svg>
    @if ($label)
        <span style="font-weight: 700; {{ $card ? 'font-size: 15px; color: var(--ink-2)' : 'font-size: 14px; color: var(--ink-muted)' }}">{{ $label }}</span>
    @endif
</div>
