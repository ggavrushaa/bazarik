{{-- Колір + розмір для вибору з картки. Стан — в Alpine (sizePicker у resources/js/app.js) --}}
@php($desk = $desk ?? false)
@if ($colors->isNotEmpty())
    <div style="display: flex; flex-direction: column; gap: {{ $desk ? '10px' : '8px' }}">
        <div class="bz-option-label"><span>Колір: <b x-text="color">{{ $firstColor }}</b></span></div>
        <div class="bz-swatches" role="group" aria-label="Колір">
            @foreach ($colors as $c)
                <button class="bz-swatch" type="button" x-on:click="setColor(@js($c['name']))" :aria-pressed="color === @js($c['name'])"
                        aria-label="{{ $c['name'] }}{{ $c['available'] ? '' : ', немає в наявності' }}" @disabled(! $c['available'])>
                    <span style="background: {{ $c['hex'] }}"></span>
                </button>
            @endforeach
        </div>
    </div>
@endif
@if ($sizes->isNotEmpty())
    <div style="display: flex; flex-direction: column; gap: {{ $desk ? '10px' : '8px' }}">
        <div class="bz-option-label">
            <span>Розмір: <b x-text="size ?? '—'">—</b></span>
            @if ($product->size_chart)<a href="{{ route('product.show', $product) }}#chart" @if ($desk) style="font-size: 14px; font-weight: 700" @endif>Таблиця розмірів</a>@endif
        </div>
        <div class="bz-sizes" role="group" aria-label="Розмір">
            @foreach ($sizes as $s)
                <button class="bz-size" type="button" x-on:click="size = @js($s)" :aria-pressed="size === @js($s)"
                        :disabled="stockOf(@js($s)) === 0" :aria-label="sizeLabel(@js($s))">
                    {{ $s }}<template x-if="stockOf(@js($s)) > 0 && stockOf(@js($s)) <= 3"><small x-text="stockOf(@js($s)) + ' шт'"></small></template>
                </button>
            @endforeach
        </div>
        @if ($product->fit_hint)
            <div class="bz-fit"><x-bz.icon name="check" size="18" style="color: var(--success)" />{{ $product->fit_hint }}</div>
        @endif
        <p class="bz-hint" x-show="needSize" x-cloak style="margin: 0; color: var(--sale); font-weight: 700">Оберіть розмір</p>
    </div>
@endif
@if ($error)
    <p class="bz-hint" style="margin: 0; color: var(--sale); font-weight: 700">{{ $error }}</p>
@endif
