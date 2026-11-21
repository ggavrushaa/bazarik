{{--
    Відгуки. Поки опублікованих немає — заглушка з макета в [дужках] (відгуки з’являться після перших замовлень).
    Телефон: заголовок «Відгуки · 4,8 з 5» + картка + «Усі N відгуків». Десктоп: підсумок ліворуч, картки в дві колонки.
--}}
@php
    $stars = fn (int|float|null $r) => str_repeat('★', (int) round((float) $r)).str_repeat('☆', 5 - (int) round((float) $r));
    $placeholders = $apparel
        ? ['Зріст 168 см · взяла M · розмір підійшов', 'Зріст 181 см · взяв L · трохи завелике']
        : [null, null];
@endphp
<section id="reviews" class="pp-reviews">
    {{-- Підсумок --}}
    <h2 class="only-mob" style="margin: 0; font-family: var(--font-display); font-weight: 800; font-size: 20px; line-height: 26px; letter-spacing: -0.01em">
        Відгуки @if ($product->reviews_count)· {{ rating($product->rating) }} з 5 @endif
    </h2>
    <div class="only-desk" style="display: flex; flex-direction: column; gap: 8px">
        <h2 class="h2">Відгуки</h2>
        @if ($product->reviews_count)
            <span style="font-family: var(--font-display); font-size: 48px; line-height: 52px; font-weight: 900">{{ rating($product->rating) }}</span>
            <span style="color: var(--brand-ink); font-size: 18px" aria-label="Оцінка {{ rating($product->rating) }} з 5">{{ $stars($product->rating) }}</span>
            <span class="mut">{{ $reviewsWord($product->reviews_count) }}</span>
        @else
            <span class="mut">Відгуків ще немає — з’являться після перших замовлень</span>
        @endif
        <a class="bz-btn bz-btn--secondary" href="{{ link_to_route_or_hash('product.review', $product) }}" style="align-self: flex-start; margin-top: 8px">Написати відгук</a>
    </div>

    {{-- Картки --}}
    <div class="pp-reviews__list">
        @forelse ($reviews as $review)
            <article @class(['pp-review', 'only-desk' => $loop->index >= 2])>
                <div class="pp-review__head">
                    <span>{{ $review->author_name }}</span>
                    <span style="color: var(--brand-ink)" aria-label="Оцінка {{ $review->rating }} з 5">{{ $stars($review->rating) }}</span>
                </div>
                @if ($review->variant_label)<span class="pp-review__tag">{{ $review->variant_label }}</span>@endif
                @if ($review->text)<p>{{ $review->text }}</p>@endif
            </article>
        @empty
            @foreach ($placeholders as $i => $tag)
                <article @class(['pp-review', 'only-desk' => $i > 0])>
                    <div class="pp-review__head"><span>[Ім’я, місто]</span><span style="color: var(--brand-ink)">★★★★★</span></div>
                    @if ($tag)<span class="pp-review__tag">{{ $tag }}</span>@endif
                    <p>[Реальний відгук покупця — додамо після перших замовлень]</p>
                </article>
            @endforeach
        @endforelse
    </div>

    @if ($product->reviews_count > 1)
        <a class="bz-btn bz-btn--secondary bz-btn--block only-mob" href="{{ link_to_route_or_hash('product.reviews', $product) }}">Усі {{ $reviewsWord($product->reviews_count) }}</a>
    @endif
</section>
