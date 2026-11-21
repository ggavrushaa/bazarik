{{--
    Фільтри каталогу (DeskCatalog, ліва колонка). Та сама розмітка — у шторці «Фільтри» на телефоні
    й у бічній панелі на планшеті. $p — префікс id, бо панель на сторінці двічі.
--}}
@php
    $fieldset = 'margin: 0; padding: 20px 0; border: 0; border-bottom: 1px solid var(--line); display: flex; flex-direction: column; gap: 12px; min-width: 0';
    $legend = 'padding: 0; float: left; width: 100%; font-size: 16px; font-weight: 800';
    $row = 'display: flex; align-items: center; gap: 10px; min-height: 36px; font-size: 15px; font-weight: 600; cursor: pointer';
    $box = 'width: 18px; height: 18px; flex: none; margin: 0; accent-color: var(--ink)';
@endphp

@if ($types->isNotEmpty())
    <fieldset style="{{ $fieldset }}">
        <legend style="{{ $legend }}">{{ $mode === 'category' ? 'Тип' : 'Категорія' }}</legend>
        @foreach ($types as $t)
            @php $on = in_array($t['slug'], $selected['type'], true); @endphp
            <label style="{{ $row }}" wire:key="{{ $p }}-type-{{ $t['slug'] }}">
                <input type="checkbox" style="{{ $box }}" @checked($on) wire:click="toggle('type', {{ \Illuminate\Support\Js::from($t['slug']) }})">
                {{ $t['name'] }}<span class="mut" style="margin-left: auto">{{ $t['count'] }}</span>
            </label>
        @endforeach
    </fieldset>
@endif

@if ($sizes->isNotEmpty())
    <fieldset style="{{ $fieldset }}">
        <legend style="{{ $legend }}">Розмір</legend>
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px">
            @foreach ($sizes as $s)
                @php $on = in_array($s['value'], $selected['size'], true); @endphp
                <button class="bz-size" type="button" style="min-width: 0" wire:key="{{ $p }}-size-{{ $s['value'] }}"
                        aria-pressed="{{ $on ? 'true' : 'false' }}" @disabled(! $on && $s['count'] === 0)
                        wire:click="toggle('size', {{ \Illuminate\Support\Js::from($s['value']) }})">{{ $s['value'] }}</button>
            @endforeach
        </div>
    </fieldset>
@endif

@if ($colors->isNotEmpty())
    <fieldset style="{{ $fieldset }}">
        <legend style="{{ $legend }}">Колір</legend>
        @foreach ($colors as $c)
            @php $on = in_array($c['value'], $selected['color'], true); $off = ! $on && $c['count'] === 0; @endphp
            <label style="{{ $row }}{{ $off ? '; opacity: .5; cursor: not-allowed' : '' }}" wire:key="{{ $p }}-color-{{ $loop->index }}">
                <input type="checkbox" style="{{ $box }}" @checked($on) @disabled($off) wire:click="toggle('color', {{ \Illuminate\Support\Js::from($c['value']) }})">
                <span style="width: 20px; height: 20px; flex: none; border-radius: 50%; background: {{ $c['hex'] ?? 'var(--surface)' }}; box-shadow: inset 0 0 0 1px rgba(0,0,0,.15)"></span>
                {{ $c['value'] }}<span class="mut" style="margin-left: auto">{{ $c['count'] }}</span>
            </label>
        @endforeach
    </fieldset>
@endif

<fieldset style="{{ $fieldset }}">
    <legend style="{{ $legend }}">Ціна, ₴</legend>
    <div style="display: flex; gap: 8px; align-items: center">
        <input class="bz-input" type="text" inputmode="numeric" autocomplete="off" aria-label="Ціна від" style="min-width: 0; width: 100%"
               placeholder="{{ $priceFrom ?? '' }}" wire:model.live.debounce.700ms="min">
        <span aria-hidden="true">—</span>
        <input class="bz-input" type="text" inputmode="numeric" autocomplete="off" aria-label="Ціна до" style="min-width: 0; width: 100%"
               placeholder="{{ $priceTo ?? '' }}" wire:model.live.debounce.700ms="max">
    </div>
</fieldset>

@if ($mode !== 'sale')
    <label style="display: flex; align-items: center; gap: 10px; padding: 20px 0; font-size: 15px; font-weight: 700; cursor: pointer">
        <input type="checkbox" style="{{ $box }}" wire:model.live="discount">Тільки зі знижкою
    </label>
@endif
