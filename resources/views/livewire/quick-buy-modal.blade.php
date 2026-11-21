{{-- «Купити в 1 клік» модалкою поверх товару: DeskQuickBuy / DeskQuickBuyUser --}}
<div x-data x-on:keydown.escape.window="$wire.open && $wire.close()">
    @if ($open)
        <div class="scrim" wire:click="close"></div>
        <div class="bz-modal" role="dialog" aria-modal="true" aria-labelledby="qbd-title"
             style="width: min(520px, calc(100vw - 48px)); padding: 32px; display: flex; flex-direction: column; gap: 20px"
             x-init="$nextTick(() => $el.querySelector('input, button:not([aria-label=Закрити])')?.focus())">
            @include('livewire.quick-buy-body', ['desk' => true])
        </div>
    @endif
</div>
