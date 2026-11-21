{{-- Вхід сторінками: AuthPhone / AuthCode / AuthName --}}
@php
    $title = match ($step) {
        'code' => 'Введіть код',
        'name' => 'Як до вас звертатися?',
        default => 'Вхід або реєстрація',
    };
@endphp
<x-layouts.app :title="$title" :footer="false">
    <livewire:auth-page :step="$step" />
</x-layouts.app>
