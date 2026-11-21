{{-- Логотип «Навіс над словом» — файл від дизайнера (bazarik-handoff/logo/svg), не набираємо текстом --}}
@props(['variant' => 'default', 'height' => 40, 'alt' => 'bazarik'])
<img src="{{ asset($variant === 'white' ? 'images/logo/bazarik-logo-white.svg' : 'images/logo/bazarik-logo.svg') }}"
     alt="{{ $alt }}" height="{{ $height }}" width="{{ round($height * 3750.6 / 1663.4) }}"
     {{ $attributes->merge(['style' => "height: {$height}px; width: auto; display: block"]) }}>
