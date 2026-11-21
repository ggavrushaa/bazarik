{{--
    Опис, характеристики, склад і догляд.
    Телефон: «Простими словами» + «Усі характеристики» (MobileProduct), «Склад і догляд» (MobileApparel).
    Десктоп: вкладки ліворуч, праворуч — таблиця розмірів (одяг) або таблиця характеристик.
--}}
@php
    $highlights = collect($product->highlights ?? [])->filter(fn ($h) => ! empty($h['value']));
    $specs = collect($product->specs ?? [])->filter(fn ($s) => ! empty($s['label']));
    $specsLine = $specs->values()->map(fn ($s, $i) => ($i ? $lcfirst($s['label']) : $s['label']).' '.$s['value'])->implode(' · ');
    $about = collect([$product->short_description, $product->description])->filter();
    $tabs = collect([
        'desc' => $about->isNotEmpty() || ($highlights->isNotEmpty() && $apparel) ? 'Опис' : null,
        'care' => $product->care ? 'Склад і догляд' : null,
    ])->filter();
    $firstTab = $tabs->keys()->first();
    $deskAside = $apparel ? (bool) $product->size_chart : $specs->isNotEmpty();
    $showDesk = $tabs->isNotEmpty() || $deskAside || (! $apparel && $highlights->isNotEmpty());
    $h2 = 'margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 20px; line-height: 26px; letter-spacing: -0.01em';
@endphp

{{-- ── Телефон ── --}}
@if ($apparel)
    @if ($product->description || $product->care)
        <div class="only-mob pp-details-boxed">
            @if ($product->description)
                <details class="pp-details">
                    <summary>Опис</summary>
                    <p>{{ $product->description }}</p>
                </details>
            @endif
            @if ($product->care)
                <details class="pp-details">
                    <summary>Склад і догляд</summary>
                    <p>{{ $product->care }}</p>
                </details>
            @endif
        </div>
    @endif
@elseif ($highlights->isNotEmpty() || $specs->isNotEmpty() || $about->isNotEmpty())
    <section class="only-mob" style="display: flex; flex-direction: column; gap: 12px">
        @if ($highlights->isNotEmpty())
            <h2 style="{{ $h2 }}">Простими словами</h2>
            @include('pages.product.highlights')
        @endif
        <div>
            @if ($about->isNotEmpty())
                <details class="pp-details">
                    <summary>Опис</summary>
                    @foreach ($about as $text)<p>{{ $text }}</p>@endforeach
                </details>
            @endif
            @if ($specs->isNotEmpty())
                <details class="pp-details">
                    <summary>Усі характеристики</summary>
                    <p>{{ $specsLine }}</p>
                </details>
            @endif
        </div>
    </section>
@endif

{{-- ── Десктоп ── --}}
@if ($showDesk)
    <section @class(['only-desk', 'pp-info', 'pp-info--single' => ! $deskAside]) @if ($apparel) id="chart" @endif
             x-init="tab = @js($firstTab ?? 'desc')">
        <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0">
            <nav class="tabs" aria-label="Про товар">
                @foreach ($tabs as $key => $label)
                    <a href="#{{ $apparel ? 'chart' : 'about' }}" :class="{ on: tab === @js($key) }" @class(['on' => $key === $firstTab])
                       x-on:click.prevent="tab = @js($key)" :aria-current="tab === @js($key)">{{ $label }}</a>
                @endforeach
                <a href="#reviews">Відгуки · {{ $product->reviews_count }}</a>
            </nav>
            @if ($tabs->has('desc'))
                <div x-show="tab === 'desc'" @if ($firstTab !== 'desc') x-cloak @endif style="display: flex; flex-direction: column; gap: 16px" id="about">
                    @foreach ($about as $text)<p class="p">{{ $text }}</p>@endforeach
                    @if (! $apparel && $highlights->isNotEmpty())
                        <h3 class="h3" style="margin-top: 8px">Простими словами</h3>
                        @include('pages.product.highlights')
                    @endif
                </div>
            @elseif (! $apparel && $highlights->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 16px">
                    <h3 class="h3">Простими словами</h3>
                    @include('pages.product.highlights')
                </div>
            @endif
            @if ($tabs->has('care'))
                <div x-show="tab === 'care'" @if ($firstTab !== 'care') x-cloak @endif>
                    <p class="p">{{ $product->care }}</p>
                </div>
            @endif
        </div>

        @if ($apparel && $product->size_chart)
            @include('pages.product.size-chart')
        @elseif (! $apparel && $specs->isNotEmpty())
            <div class="bz-table-wrap" style="align-self: start">
                <table class="bz-table pp-specs">
                    <caption style="caption-side: top; text-align: left; padding: 12px 12px 8px; font-size: 16px; font-weight: 800">Усі характеристики</caption>
                    <tbody>
                        @foreach ($specs as $s)
                            <tr><th scope="row">{{ $s['label'] }}</th><td>{{ $s['value'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endif
