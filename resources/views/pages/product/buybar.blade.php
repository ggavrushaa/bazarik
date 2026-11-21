{{-- Нижня липка панель на телефоні: ціна + кнопка --}}
<div class="bz-buybar only-mob pp-buybar">
    <x-shop.price :product="$product" />
    @if ($apparel)
        <button class="bz-btn bz-btn--l" type="button" :class="size ? 'bz-btn--primary' : 'bz-btn--secondary'"
                x-on:click="buy(true)" x-text="buttonText()">Оберіть розмір</button>
    @else
        <button class="bz-btn bz-btn--primary bz-btn--l" type="button"
                x-on:click="Livewire.dispatch('add-variant', { id: {{ $buyVariant->id }}, quantity: 1 })">Додати в кошик</button>
    @endif
</div>
