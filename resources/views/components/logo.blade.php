{{-- Логотип «Навіс над словом». Тимчасова SVG-версія, фінальну в кривих замінимо файлом від дизайнера --}}
@props(['class' => 'h-9'])
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 150 52" role="img" aria-label="bazarik">
    <path d="M18 4h114l10 13H8z" fill="#FF7A1A"/>
    <path d="M8 17h134v5c-5.6 0-5.6 5-11.2 5s-5.6-5-11.2-5-5.6 5-11.2 5-5.6-5-11.2-5-5.6 5-11.2 5-5.6-5-11.2-5-5.6 5-11.2 5-5.6-5-11.2-5-5.6 5-11.2 5-5.6-5-11.2-5-5.6 5-11.2 5S13.6 22 8 22z" fill="#D95E0A"/>
    <text x="75" y="48" text-anchor="middle" font-family="Nunito, sans-serif" font-weight="900" font-size="22" fill="currentColor">bazarik</text>
</svg>
