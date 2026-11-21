{{-- «Показати ще 24» + «Показано 24 з 42» (DeskCatalog) --}}
@if ($total > \App\Livewire\Catalog::PER_PAGE)
    <div style="display: flex; flex-direction: column; align-items: center; gap: 12px">
        @if ($next > 0)
            <button class="bz-btn bz-btn--secondary bz-btn--l" type="button" style="padding: 0 40px" wire:click="more" wire:loading.attr="disabled" wire:target="more">Показати ще {{ $next }}</button>
        @endif
        <span class="mut" style="text-align: center">Показано {{ $shown }} з {{ $total }}</span>
    </div>
@endif
