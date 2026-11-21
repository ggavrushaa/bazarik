{{--
    Галерея на N фото. Телефон: стрічка зі scroll-snap + мініатюри під нею (якщо фото більше одного).
    Десктоп: мініатюри ліворуч (від 1024px) або під фото + велике фото.
--}}
@php
    $ratio = $apparel ? '4 / 5' : '1 / 1';
    $video = $product->video_seconds
        ? sprintf('Відео %d:%02d', intdiv($product->video_seconds, 60), $product->video_seconds % 60)
        : null;
@endphp
<div class="pp-gallery">
    {{-- ── Телефон ── --}}
    <div class="only-mob" style="display: flex; flex-direction: column; gap: 10px">
        <div style="position: relative">
            <div class="pp-mgal" x-ref="track" style="aspect-ratio: {{ $ratio }}"
                 x-on:scroll.debounce.100ms="photo = Math.round($el.scrollLeft / $el.clientWidth)">
                @forelse ($images as $i => $image)
                    <div class="pp-mgal__slide">
                        <img src="{{ $image->url }}" alt="{{ $image->alt ?? $product->name }}" @if ($i > 0) loading="lazy" @endif>
                    </div>
                @empty
                    <div class="pp-mgal__slide"></div>
                @endforelse
            </div>
            <div style="position: absolute; top: 12px; left: 12px; display: flex; gap: 4px">
                @if ($product->discount_percent)<span class="bz-badge bz-badge--sale">−{{ $product->discount_percent }}%</span>@endif
                @if ($product->badge)<span class="bz-badge bz-badge--{{ $product->badge->value }}">{{ $product->badge->getLabel() }}</span>@endif
            </div>
            @if ($video)
                @include('pages.product.video-button', ['style' => 'position: absolute; bottom: 12px; left: 12px'])
            @endif
        </div>
        @if ($images->count() > 1)
            <div class="pp-mthumbs">
                @foreach ($images as $i => $image)
                    <button type="button" class="pp-mthumb" aria-label="Фото {{ $i + 1 }}" x-on:click="go({{ $i }})"
                            :aria-current="photo === {{ $i }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}">
                        <img src="{{ $image->url }}" alt="" loading="lazy">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Десктоп ── --}}
    <div @class(['only-desk', 'pp-dgal', 'has-thumbs' => $images->count() > 1])>
        @if ($images->count() > 1)
            <div class="pp-dthumbs">
                @foreach ($images as $i => $image)
                    <button type="button" class="pp-dthumb img" aria-label="Фото {{ $i + 1 }}" x-on:click="photo = {{ $i }}"
                            :aria-current="photo === {{ $i }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}"
                            style="aspect-ratio: {{ $apparel ? '4 / 5' : '1 / 1' }}">
                        <img src="{{ $image->url }}" alt="" loading="lazy">
                    </button>
                @endforeach
            </div>
        @endif
        <div class="img pp-dmain" style="aspect-ratio: {{ $ratio }}">
            @forelse ($images as $i => $image)
                <img src="{{ $image->url }}" alt="{{ $image->alt ?? $product->name }}"
                     @if ($i > 0) x-cloak loading="lazy" @endif x-show="photo === {{ $i }}">
            @empty
            @endforelse
            @if ($video)
                @include('pages.product.video-button', ['style' => 'position: absolute; bottom: 16px; left: 16px'])
            @endif
        </div>
    </div>
</div>
