{{--
    Таймлайн статусу замовлення (AccountOrder, StateTracking, DeskTracking).
    Параметри: $steps (TrackingController::timeline), $desk (чорні крапки DeskTracking; інакше — зелені), $lines (з’єднувальні лінії).
--}}
@php
    $desk ??= false;
    $lines ??= true;
    $doneColor = $desk ? 'var(--ink)' : 'var(--success-strong)';
    $size = $desk || $lines ? 24 : 22;
@endphp
<ol style="margin: 0; padding: 0; list-style: none">
    @foreach ($steps as $i => $step)
        @php $next = $steps[$i + 1] ?? null; @endphp
        <li style="position: relative; display: flex; gap: {{ $desk ? 16 : 12 }}px; padding-bottom: {{ $desk ? 24 : ($lines ? 18 : 14) }}px">
            @if ($lines && $next)
                <span aria-hidden="true" style="position: absolute; left: {{ $size / 2 - 1 }}px; top: {{ $size + 2 }}px; bottom: {{ $desk ? 0 : -6 }}px; width: 2px; background: {{ $next['done'] ? $doneColor : 'var(--line)' }}"></span>
            @endif
            <span aria-hidden="true" style="width: {{ $size }}px; height: {{ $size }}px; border-radius: 99px; flex: none; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; z-index: 1; color: var(--on-ink); {{ $step['done'] ? "background: $doneColor; border: 2px solid $doneColor" : 'background: var(--bg); border: 2px solid var(--line-strong)' }}">
                @if ($step['done'])<x-bz.icon name="check" size="14" stroke="3" />@endif
            </span>
            <span style="display: flex; flex-direction: column; gap: 2px">
                <b style="font-size: {{ $desk ? 16 : ($lines ? 15 : 14) }}px; font-weight: 800; color: {{ $step['done'] ? 'var(--ink)' : 'var(--ink-muted)' }}">
                    {{ $step['label'] }}<span class="sr">{{ $step['done'] ? ' — виконано' : ' — очікується' }}</span>
                </b>
                @if ($step['note'])
                    <span @if ($desk) class="mut" @else style="font-size: 13px; color: var(--ink-muted); font-weight: 600" @endif>{{ $step['note'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>
