<span>
    @php($label = $count ? 'Кошик: '.plural_uk($count, 'товар', 'товари', 'товарів').', '.money($subtotal) : 'Кошик порожній')
    @if ($variant === 'desk')
        <a class="dk-act" href="{{ route('cart') }}" aria-label="{{ $label }}">
            <x-bz.icon name="cart" size="24" />{{ $count ? money($subtotal) : 'Кошик' }}
            @if ($count)<span class="n" aria-hidden="true">{{ $count }}</span>@endif
        </a>
    @else
        <a class="bz-icon-btn" href="{{ route('cart') }}" aria-label="{{ $label }}">
            <x-bz.icon name="cart" />
            @if ($count)<span class="bz-count" aria-hidden="true">{{ $count }}</span>@endif
        </a>
    @endif
</span>
