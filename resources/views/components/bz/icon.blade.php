{{--
    Лінійні іконки з макетів: сітка 24px, штрих 2px, круглі кінці, currentColor.
    <x-bz.icon name="truck" class="size-4" />   ·   filled-іконка лише одна: star.
--}}
@props(['name', 'size' => null, 'stroke' => 2])
@php
    $paths = [
        'back' => '<path d="M15 6l-6 6 6 6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
        'truck' => '<path d="M3 7h11v9H3z M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'menu' => '<path d="M4 6h16 M4 12h16 M4 18h10"/>',
        'cart' => '<path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L20 8H6.2"/><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
        'box' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z M4 7.5l8 4.5 8-4.5 M12 12v9"/>',
        'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z M10 21h4"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18 M12 3a14 14 0 0 1 0 18 M12 3a14 14 0 0 0 0 18"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5V14 M12 17h.01"/>',
        'logout' => '<path d="M15 4h4v16h-4 M10 8l-4 4 4 4 M6 12h10"/>',
        'check' => '<path d="M5 12l5 5 9-10"/>',
        'bolt' => '<path d="M13 3L5 13h6l-1 8 8-10h-6z"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'package' => '<path d="M3 8l9-5 9 5v8l-9 5-9-5z M3 8l9 5 9-5 M12 13v8"/>',
        'chat' => '<path d="M4 5h16v11H8l-4 4z"/>',
        'cash' => '<rect x="3" y="7" width="18" height="10" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
        'return' => '<path d="M4 9h11a5 5 0 0 1 0 10H8 M8 5L4 9l4 4"/>',
        'close' => '<path d="M6 6l12 12 M18 6L6 18"/>',
        'plus' => '<path d="M12 5v14 M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z M9 12l2 2 4-4"/>',
        'trash' => '<path d="M5 7h14 M10 7V5h4v2 M7 7l1 13h8l1-13"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'card' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>',
        'filter' => '<path d="M4 6h16 M7 12h10 M10 18h4"/>',
        'play' => '<path d="M7 4l13 8-13 8z"/>',
    ];
    $filled = ['star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>'];
@endphp
@if (isset($filled[$name]))
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" @if ($size) width="{{ $size }}" height="{{ $size }}" @endif {{ $attributes }}>{!! $filled[$name] !!}</svg>
@else
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" @if ($size) width="{{ $size }}" height="{{ $size }}" @endif {{ $attributes }}>{!! $paths[$name] ?? '' !!}</svg>
@endif
