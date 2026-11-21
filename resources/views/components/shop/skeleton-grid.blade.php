{{--
    Сітка скелетонів карток з навісом-завантажувачем (StateLoading / DeskLoading).
    До 768px: навіс над сіткою 2×N; від 768px: сітка .g3 і навіс у картці поверх неї.
    <x-shop.skeleton-grid />  ·  <x-shop.skeleton-grid :count="4" :loader="false" class="g4" />
--}}
@props(['count' => 6, 'loader' => true, 'label' => 'Розкладаємо товар…'])
<div {{ $attributes->class(['flex flex-col gap-4']) }}>
    @if ($loader)
        <x-shop.loader :label="$label" class="only-mob" />
    @endif
    <div class="relative">
        <div class="g3">
            @for ($i = 0; $i < $count; $i++)
                {{-- На телефоні — 4 картки, як у макеті, решта ховається --}}
                <x-shop.skeleton-card :class="$i >= 4 ? 'max-md:hidden' : ''" />
            @endfor
        </div>
        @if ($loader)
            <div class="only-desk" style="position: absolute; left: 50%; top: 120px; transform: translateX(-50%)">
                <x-shop.loader card :label="$label" />
            </div>
        @endif
    </div>
</div>
