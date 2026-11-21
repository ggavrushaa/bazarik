<div style="display: contents">
    <button class="{{ $btn }}" type="button" wire:click="reorder" wire:loading.attr="disabled">{{ $label }}</button>
    @if ($error)
        <span role="alert" style="font-size: 13px; font-weight: 700; color: var(--sale)">{{ $error }}</span>
    @endif
</div>
